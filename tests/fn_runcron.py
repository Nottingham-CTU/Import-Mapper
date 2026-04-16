# Generated from Selenium IDE
# Test name: fn runcron
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_fn_runcron:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_fn_runcron(self):
    assert len(self.driver.find_elements(By.ID, "south")) > 0
    self.vars["_returnurl"] = self.driver.execute_script("return window.location.href")
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ExternalModules/manager/control_center.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"import_mapper\"] button.external-modules-cron-test-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "#external-modules-cron-table tr[data-cron=\"process_import_jobs\"][data-prefix=\"import_mapper\"] button")))
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-cron-table tr[data-cron=\"process_import_jobs\"][data-prefix=\"import_mapper\"] button").click()
    WebDriverWait(self.driver, 60).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[contains(@class,'module-cron-list')][contains(.,'Finished External Module Cron')]")))
    self.driver.execute_script("//SETDESC:Return to imports page")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-cron-modal .close").click()
    self.driver.execute_script("window.location = arguments[0]", self.vars["_returnurl"])
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
