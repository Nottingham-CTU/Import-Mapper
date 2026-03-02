import { useState, useRef, useCallback } from "react";
import { mappingApi } from "../api/mappingApi";

function getPollInterval(jobs) {
  if (jobs.some(j => j.status === "in_progress")) return 10000;
  if (jobs.length > 0) return 30000;
  return 60000;
}

export function useImportJobs() {
  const [logs, setLogs] = useState([]);
  const [activeJobs, setActiveJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pendingCancel, setPendingCancel] = useState({});
  const pollTimeoutRef = useRef(null);

  function clearPendingCancelForInactiveJobs(jobs) {
    const activeIds = new Set(jobs.map(j => j.jobId));
    setPendingCancel(prev => {
      const next = {};
      for (const id of Object.keys(prev)) {
        if (activeIds.has(id)) next[id] = prev[id];
      }
      return next;
    });
  }

  const loadLogs = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const logs = await mappingApi.getLogs();
      setLogs(logs);
    } catch (error) {
      console.error("Error loading logs:", error);
    } finally {
      if (!silent) setLoading(false);
    }
  }, []);

  const pollActive = useCallback(async () => {
    try {
      const jobs = await mappingApi.getActiveJobs();
      setActiveJobs(jobs);
      clearPendingCancelForInactiveJobs(jobs);
      if (jobs.length === 0) {
        await loadLogs(true);
      }
      pollTimeoutRef.current = setTimeout(pollActive, getPollInterval(jobs));
    } catch (error) {
      console.error("Error polling active jobs:", error);
    }
  }, [loadLogs]);

  const loadAll = useCallback(async (silent = false) => {
    if (!silent) setLoading(true);
    try {
      const [logs, jobs] = await Promise.all([
        mappingApi.getLogs(),
        mappingApi.getActiveJobs(),
      ]);
      setLogs(logs);
      setActiveJobs(jobs);
      clearPendingCancelForInactiveJobs(jobs);
      pollTimeoutRef.current = setTimeout(pollActive, getPollInterval(jobs));
    } catch (error) {
      console.error("Error loading imports:", error);
    } finally {
      if (!silent) setLoading(false);
    }
  }, [pollActive]);

  const cancelJob = useCallback(async (jobId) => {
    setPendingCancel(prev => ({ ...prev, [jobId]: true }));
    try {
      await mappingApi.cancelImportJob(jobId);
      clearTimeout(pollTimeoutRef.current);
      await pollActive();
    } catch (error) {
      setPendingCancel(prev => ({ ...prev, [jobId]: false }));
      throw error;
    }
  }, [pollActive]);

  function stopPolling() {
    clearTimeout(pollTimeoutRef.current);
  }

  return { logs, activeJobs, loading, pendingCancel, loadAll, cancelJob, stopPolling };
}
