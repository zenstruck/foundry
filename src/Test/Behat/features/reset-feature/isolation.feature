Feature: Database isolation per feature - Part 1

  Scenario: First scenario creates data
    Given there is a contact A with
      | name     |
      | John Doe |
    Then 1 contact should exist

  Scenario: Second scenario sees first scenario's data (no reset within feature)
    Then 1 contact should exist

  Scenario: Third scenario also sees accumulated data
    Given there is a contact B with
      | name     |
      | Jane Doe |
    Then 2 contacts should exist

  Scenario: Fourth scenario can access objects created in previous scenarios via ObjectRegistry
    Then contact A should have properties
      | name     |
      | John Doe |
    And contact B should have properties
      | name     |
      | Jane Doe |
