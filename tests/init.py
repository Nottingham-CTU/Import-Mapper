# Generated from Selenium IDE
# Test name: init
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_init:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_init(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='table-proj_table'][contains(.,'Import Mapper Test')]")) == 0
    self.vars["projtypes"] = self.driver.execute_script("return ['C','L']")
    for self.vars["projtype"] in self.vars["projtypes"]:
      self.driver.find_element(By.LINK_TEXT, "New Project").click()
      self.driver.find_element(By.ID, "app_title").send_keys("Import Mapper Test "+self.vars["projtype"])
      self.driver.find_element(By.ID, "purpose").find_element(By.CSS_SELECTOR, "*[value='0']").click()
      self.driver.find_element(By.ID, "project_template_radio1").click()
      if self.driver.execute_script("return (arguments[0] == 'C')", self.vars["projtype"]):
        self.driver.find_element(By.XPATH, "//table[@id='table-template_projects_list']//tr[contains(.,'Classic Database')]//input").click()
      else:
        self.driver.find_element(By.XPATH, "//table[@id='table-template_projects_list']//tr[contains(.,'Longitudinal Database (1 arm)')]//input").click()
      self.driver.find_element(By.CSS_SELECTOR, ".btn-primaryrc").click()
      time.sleep(5)
      self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"/UserRights/index.php\"]").click()
      self.driver.find_element(By.ID, "new_rolename").send_keys("Admin")
      self.driver.find_element(By.ID, "createRoleBtn").click()
      None if (element := self.driver.find_element(By.NAME, "design")).is_selected() else element.click()
      None if (element := self.driver.find_element(By.NAME, "data_access_groups")).is_selected() else element.click()
      None if (element := self.driver.find_element(By.NAME, "record_create")).is_selected() else element.click()
      self.driver.find_element(By.CSS_SELECTOR, "button[style*=\"bold\"]").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Admin')]")))
      time.sleep(3)
      self.driver.find_element(By.ID, "new_rolename").send_keys("Importer")
      self.driver.find_element(By.ID, "createRoleBtn").click()
      None if (element := self.driver.find_element(By.NAME, "record_create")).is_selected() else element.click()
      self.driver.find_element(By.CSS_SELECTOR, "button[style*=\"bold\"]").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Importer')]")))
      time.sleep(3)
      self.driver.find_element(By.ID, "new_rolename").send_keys("Standard")
      self.driver.find_element(By.ID, "createRoleBtn").click()
      None if (element := self.driver.find_element(By.NAME, "record_create")).is_selected() else element.click()
      self.driver.find_element(By.CSS_SELECTOR, "button[style*=\"bold\"]").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Standard')]")))
      time.sleep(3)
      self.driver.find_element(By.ID, "new_username_assign").send_keys("user1")
      self.driver.find_element(By.ID, "assignUserBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.visibility_of_element_located((By.ID, "notify_email_role")))
      None if not (element := self.driver.find_element(By.ID, "notify_email_role")).is_selected() else element.click()
      self.driver.find_element(By.ID, "user_role").find_element(By.XPATH, "//option[. = 'Admin']").click()
      self.driver.find_element(By.ID, "assignDagRoleBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Admin')][contains(.,'user1')]")))
      time.sleep(3)
      self.driver.find_element(By.ID, "new_username_assign").send_keys("user2")
      self.driver.find_element(By.ID, "assignUserBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.visibility_of_element_located((By.ID, "notify_email_role")))
      None if not (element := self.driver.find_element(By.ID, "notify_email_role")).is_selected() else element.click()
      self.driver.find_element(By.ID, "user_role").find_element(By.XPATH, "//option[. = 'Importer']").click()
      self.driver.find_element(By.ID, "assignDagRoleBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Importer')][contains(.,'user2')]")))
      time.sleep(3)
      self.driver.find_element(By.ID, "new_username_assign").send_keys("user3")
      self.driver.find_element(By.ID, "assignUserBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.visibility_of_element_located((By.ID, "notify_email_role")))
      None if not (element := self.driver.find_element(By.ID, "notify_email_role")).is_selected() else element.click()
      self.driver.find_element(By.ID, "user_role").find_element(By.XPATH, "//option[. = 'Standard']").click()
      self.driver.find_element(By.ID, "assignDagRoleBtn").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='table-user_rights_roles_table']//tr[contains(.,'Standard')][contains(.,'user3')]")))
      self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ProjectSetup/index.php\"]").click()
      self.driver.find_element(By.ID, "enableRepeatingFormsEventsBtn").click()
      if self.driver.execute_script("return (arguments[0] == 'C')", self.vars["projtype"]):
        None if (element := self.driver.find_element(By.XPATH, "//*[@id='table-repeat_setup']//tr[contains(.,'Month 2 Data')]//input[@type='checkbox']")).is_selected() else element.click()
      else:
        self.driver.find_element(By.XPATH, "//*[@id='table-repeat_setup']//tr[contains(.,'Visit 2')]//select").find_element(By.CSS_SELECTOR, "*[value='PARTIAL']").click()
        None if (element := self.driver.find_element(By.XPATH, "//*[@id='table-repeat_setup']//tr[contains(.,'Visit 2')]//div[@class='repeat_event_form_div'][contains(.,'Visit Lab Data')]//input")).is_selected() else element.click()
      self.driver.find_element(By.XPATH, "//div[@aria-describedby='repeatingInstanceEnableDialog']//button[contains(.,'Save')]").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//button[@id='enableRepeatingFormsEventsBtn'][contains(.,'Modify')]")))
      self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ExternalModules/manager/project.php\"]").click()
      self.driver.find_element(By.ID, "external-modules-enable-modules-button").click()
      self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"import_mapper\"] button.enable-button").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"import_mapper\"] button.external-modules-configure-button")))
      self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"import_mapper\"] button.external-modules-configure-button").click()
      WebDriverWait(self.driver, 30).until(expected_conditions.visibility_of_element_located((By.NAME, "admin-roles____0")))
      self.driver.execute_script("$('[name=\"admin-roles____0\"]')[0].selectedIndex=1;$('[name=\"importer-roles____0\"]')[0].selectedIndex=2")
      time.sleep(0.5)
      self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal button.save").click()
      self.driver.find_element(By.LINK_TEXT, "My Projects").click()
