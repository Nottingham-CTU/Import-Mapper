# Generated from Selenium IDE
# Test name: t02 Import into longitudinal project
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from fn_mappingselect import Test_fn_mappingselect as Sub1
from fn_runcron import Test_fn_runcron as Sub2

class Test_02_Import_into_longitudinal_project:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_02_Import_into_longitudinal_project(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='table-proj_table'][contains(.,'Import Mapper Test L')]")) > 0
    self.driver.find_element(By.LINK_TEXT, "Import Mapper Test L").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=import_mapper\"][href*=\"page=pages%2Fdashboard\"]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'New mapping')]").click()
    self.driver.find_element(By.ID, "map-name").send_keys(Keys.LEFT, "Test2a")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    self.driver.find_element(By.ID, "csv").send_keys("REPODIR/tests/testdata.csv")
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, ".alert-success")))
    self.driver.execute_script("//SETDESC:Verify CSV fields.")
    self.driver.find_element(By.CSS_SELECTOR, "div.mb-3").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    self.vars["row"] = "1"
    self.vars["field"] = "1"
    self.vars["value"] = "int2"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "study_id"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Add field mapping')]").click()
    self.vars["row"] = "2"
    self.vars["field"] = "1"
    self.vars["value"] = "string1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "first_name"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Add field mapping')]").click()
    self.vars["row"] = "3"
    self.vars["field"] = "1"
    self.vars["value"] = "string2"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "last_name"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "(//*[@id='import-wrangler']//button[contains(.,'Add')])[3]").click()
    self.driver.find_element(By.ID, "combineEnabled").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Additional Fields')]//button[contains(.,'Add Field')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Additional Fields')]//select").find_element(By.CSS_SELECTOR, "*[value='string3']").click()
    None if (element := self.driver.find_element(By.ID, "fieldRegex-0")).is_selected() else element.click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Additional Fields')]//input[contains(@placeholder,'Pattern')]").send_keys(Keys.LEFT, "/^(..).*/")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Additional Fields')]//input[contains(@placeholder,'Replacement')]").send_keys(Keys.LEFT, "$1")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Additional Fields')]//button[contains(.,'Save')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Add field mapping')]").click()
    self.vars["row"] = "4"
    self.vars["field"] = "1"
    self.vars["value"] = "dateymd"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "date_enrolled"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "input[aria-label=\"Enable record matching\"]")).is_selected() else element.click()
    self.vars["row"] = "1"
    self.vars["field"] = "1"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "int2::enrollment_arm_1::study_id"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(@class,'btn-success')][contains(.,'Save')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'New mapping')]").click()
    self.driver.find_element(By.ID, "map-name").send_keys(Keys.LEFT, "Test2b")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    self.driver.find_element(By.ID, "csv").send_keys("REPODIR/tests/testdata.csv")
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, ".alert-success")))
    self.driver.execute_script("//SETDESC:Verify CSV fields.")
    self.driver.find_element(By.CSS_SELECTOR, "div.mb-3").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    self.vars["row"] = "1"
    self.vars["field"] = "1"
    self.vars["value"] = "int2"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "study_id"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Add field mapping')]").click()
    self.vars["row"] = "2"
    self.vars["field"] = "1"
    self.vars["value"] = "datedmy"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "final_visit_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "completion_data"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "4"
    self.vars["value"] = "date_visit_4"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "(//*[@id='import-wrangler']//button[contains(.,'Add')])[2]").click()
    self.driver.find_element(By.ID, "dateConversionEnabled").click()
    self.driver.find_element(By.ID, "dateFormat").find_element(By.CSS_SELECTOR, "*[value='DMY']").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'modal')]//div[contains(.,'Value Mapping')]//button[contains(.,'Save')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Continue')]").click()
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "input[aria-label=\"Enable record matching\"]")).is_selected() else element.click()
    self.vars["row"] = "1"
    self.vars["field"] = "1"
    self.vars["value"] = "enrollment_arm_1"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "2"
    self.vars["value"] = "demographics"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.vars["field"] = "3"
    self.vars["value"] = "int2::enrollment_arm_1::study_id"
    sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_mappingselect() # Run fn mappingselect
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(@class,'btn-success')][contains(.,'Save')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'card-header')][contains(.,'Test2a')]//button[contains(.,'Import')]").click()
    self.driver.find_element(By.ID, "csv").send_keys("REPODIR/tests/testdata.csv")
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//div[contains(@class,'mb-3')][contains(.,'Sortir')]")))
    self.driver.execute_script("//SETDESC:Verify CSV preview.")
    self.driver.find_element(By.CSS_SELECTOR, "div.mb-3").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Import')]").click()
    WebDriverWait(self.driver, 60).until(expected_conditions.presence_of_element_located((By.XPATH, "//div[contains(@class,'alert-success')][contains(.,'Import Queued')]")))
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//a[contains(.,'View imports')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]")))
    sub=Sub2();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_runcron() # Run fn runcron
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]//tr[contains(.,'Test2a')][contains(.,'Completed')]")) > 0
    self.driver.execute_script("//SETDESC:Verify import completed.")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.XPATH, "//a[@href='/'][contains(.,'Mappings')]").click()
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//div[contains(@class,'card-header')][contains(.,'Test2b')]//button[contains(.,'Import')]").click()
    self.driver.find_element(By.ID, "csv").send_keys("REPODIR/tests/testdata.csv")
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//div[contains(@class,'mb-3')][contains(.,'Sortir')]")))
    self.driver.execute_script("//SETDESC:Verify CSV preview.")
    self.driver.find_element(By.CSS_SELECTOR, "div.mb-3").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//button[contains(.,'Import')]").click()
    WebDriverWait(self.driver, 60).until(expected_conditions.presence_of_element_located((By.XPATH, "//div[contains(@class,'alert-success')][contains(.,'Import Queued')]")))
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//a[contains(.,'View imports')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]")))
    sub=Sub2();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_runcron() # Run fn runcron
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]//tr[contains(.,'Test2b')][contains(.,'Completed')]")) > 0
    self.driver.execute_script("//SETDESC:Verify import completed.")
    self.driver.find_element(By.XPATH, "//*[@id='import-wrangler']//table[contains(.,'Mapping Name')]").send_keys("SAVESCREENSHOT")
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataEntry/record_status_dashboard.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataEntry/index.php\"][href*=\"id=11\"][href*=\"page=demographics\"]").click()
    self.driver.execute_script("//SETDESC:Assert value for First Name")
    self.driver.find_element(By.NAME, "first_name").send_keys("SAVESCREENSHOT")
    assert self.driver.find_element(By.NAME, "first_name").get_attribute("value") == "Oublier"
    self.driver.execute_script("//SETDESC:Assert value for Last Name")
    self.driver.find_element(By.NAME, "last_name").send_keys("SAVESCREENSHOT")
    assert self.driver.find_element(By.NAME, "last_name").get_attribute("value") == "Prendre Ri"
    self.driver.execute_script("//SETDESC:Assert value for Date")
    self.driver.find_element(By.NAME, "date_enrolled").send_keys("SAVESCREENSHOT")
    assert self.driver.find_element(By.NAME, "date_enrolled").get_attribute("value") == "2002-06-14"
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataEntry/record_home.php\"][href*=\"id=11\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataEntry/index.php\"][href*=\"id=11\"][href*=\"page=completion_data\"]").click()
    self.driver.execute_script("//SETDESC:Assert value for Date")
    self.driver.find_element(By.NAME, "date_visit_4").send_keys("SAVESCREENSHOT")
    assert self.driver.find_element(By.NAME, "date_visit_4").get_attribute("value") == "2023-11-09"
