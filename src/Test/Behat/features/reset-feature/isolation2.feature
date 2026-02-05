Feature: Database isolation per feature - Part 2

  Scenario: First scenario of new feature should have empty database (reset between features)
    Then 0 contacts should exist

  Scenario: Second scenario in new feature sees first scenario's data
    Given there is a "contact" "C" with
      | name      |
      | Alice Doe |
    Then 1 contact should exist

  Scenario: Third scenario confirms data persists within feature
    Then 1 contact should exist

  Scenario: Could access data created in previous scenario
    Then "contact" "C" should have properties
      | name      |
      | Alice Doe |
