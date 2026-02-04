@skip-with-native-dama
Feature: Skip database reset with @noResetDB tag

  Scenario: First scenario creates data
    Given a contact is created
    Then 1 contact should exist

  @noResetDB
  Scenario: Data persists with @noResetDB tag
    Then 1 contact should exist
    Given a contact is created
    Then 2 contacts should exist

  Scenario: Normal reset resumes after @noResetDB
    Then 0 contacts should exist
