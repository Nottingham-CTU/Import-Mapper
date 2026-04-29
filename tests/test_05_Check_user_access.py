# Generated from Selenium IDE
# Test name: t05 Check user access
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from fn_switchuser import Test_fn_switchuser as Sub1

class Test_05_Check_user_access:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_05_Check_user_access(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='table-proj_table'][contains(.,'Import Mapper Test C')]")) > 0
    assert len(self.driver.find_elements(By.XPATH, "//*[@id='table-proj_table'][contains(.,'Import Mapper Test L')]")) > 0
    self.vars["users"] = self.driver.execute_script("return ['user1','user2','user3']")
    self.vars["projtypes"] = self.driver.execute_script("return ['C','L']")
    for self.vars["username"] in self.vars["users"]:
      sub=Sub1();sub.driver=self.driver;sub.vars=self.vars;sub.test_fn_switchuser() # Run fn switchuser
      for self.vars["projtype"] in self.vars["projtypes"]:
        self.driver.find_element(By.LINK_TEXT, "Import Mapper Test "+self.vars["projtype"]).click()
        if self.driver.execute_script("return (arguments[0] == 'user3')", self.vars["username"]):
          self.driver.execute_script("//SAVEDESC:Assert Import Mapper link not present")
          assert len(self.driver.find_elements(By.CSS_SELECTOR, "a[href*=\"prefix=import_mapper\"][href*=\"page=pages%2Fdashboard\"]")) == 0
        else:
          self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"prefix=import_mapper\"][href*=\"page=pages%2Fdashboard\"]").click()
          time.sleep(3)
          self.driver.execute_script("$('#import-wrangler').css('width','550px').css('height','250px').css('overflow','hidden')")
          if self.driver.execute_script("return (arguments[0] == 'user1')", self.vars["username"]):
            self.driver.execute_script("//SETDESC:Assert 'New mapping', 'Delete', 'Edit' and 'Copy' buttons present")
            self.driver.find_element(By.ID, "import-wrangler").send_keys("SAVESCREENSHOT")
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'New mapping')]")) > 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Delete')]")) > 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Edit')]")) > 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Copy')]")) > 0
          else:
            self.driver.execute_script("//SETDESC:Assert 'New mapping', 'Delete', 'Edit' and 'Copy' buttons not present")
            self.driver.find_element(By.ID, "import-wrangler").send_keys("SAVESCREENSHOT")
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'New mapping')]")) == 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Delete')]")) == 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Edit')]")) == 0
            assert len(self.driver.find_elements(By.XPATH, "//button[contains(.,'Copy')]")) == 0
          self.driver.execute_script("//SETDESC:Assert 'Import' button present")
          self.driver.find_element(By.ID, "import-wrangler").send_keys("SAVESCREENSHOT")
          assert len(self.driver.find_elements(By.XPATH, "//button[contains(@class,'btn-primary')][contains(.,'Import')]")) > 0
        self.driver.find_element(By.LINK_TEXT, "My Projects").click()
