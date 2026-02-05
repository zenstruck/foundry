@resetDB
Feature: Manual database isolation (disabled mode) - Part 3

  Scenario: Ensure DB is fresh
    Then 0 contacts should exist

  Scenario: Create a contact
    Given there is a contact
    Then 1 contacts should exist

  Scenario: Ensure contact still exists
    Then 1 contacts should exist
