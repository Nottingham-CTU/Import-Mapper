# Generated from Selenium IDE
# Test name: fn mappingselect
# Comment: Arguments: row, field (each 1-indexed), value
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_fn_mappingselect:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_fn_mappingselect(self):
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "table.table tbody tr")))
    self.driver.execute_script("$('#import-wrangler .table tbody tr').each(function(i){$(this).attr('data-testing',''+(i+1))})")
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[data-testing=\""+self.vars["row"]+"\"]")))
    self.driver.execute_script("$('[data-testing=\"' + arguments[0] + '\"] select').eq(arguments[1]-1)[0].value=arguments[2]", self.vars["row"], self.vars["field"], self.vars["value"])
    self.driver.execute_script("$('[data-testing=\"' + arguments[0] + '\"] select').eq(arguments[1]-1)[0].dispatchEvent(new Event('change', { bubbles: true }))", self.vars["row"], self.vars["field"])
    time.sleep(0.5)
    self.driver.execute_script("$('#import-wrangler .table tbody tr').each(function(i){$(this).attr('data-testing',''+(i+1))})")
    self.driver.execute_script("$('[data-testing=\"' + arguments[0] + '\"] select').eq(arguments[1]-1).attr('data-screenshot','1')", self.vars["row"], self.vars["field"])
    self.driver.execute_script("//SETDESC:Select \"arguments[0]\"", self.vars["value"])
    self.driver.find_element(By.CSS_SELECTOR, "[data-screenshot=\"1\"]").send_keys("SAVESCREENSHOT")
    self.driver.execute_script("$('[data-testing=\"' + arguments[0] + '\"] select').eq(arguments[1]-1).attr('data-screenshot','0')", self.vars["row"], self.vars["field"])
