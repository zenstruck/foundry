Feature: No database isolation (disabled mode)

  Scenario: First scenario creates data
    Given a contact A is created with properties
      | name     |
      | John Doe |
    Then 1 contact should exist

  Scenario: Second scenario sees previous data (no reset)
    Then 1 contact should exist
    Given a contact B is created with properties
      | name     |
      | Jane Doe |
    Then 2 contacts should exist

  Scenario: Third scenario sees all accumulated data
    Then 2 contacts should exist
