Feature: Database isolation per scenario

  Scenario: First scenario creates data
    Given a contact A is created with properties
      | name     |
      | John Doe |
    Then 1 contact should exist

  Scenario: Second scenario should have empty database (reset worked)
    Then 0 contacts should exist

  Scenario Outline: Third scenario creates different data
    Given a contact B is created with properties
      | name     |
      | <name> |
    Then 1 contact should exist
    Examples:
      | name |
      | Jane Doe |
      | John Doe |

  Scenario: Fourth scenario confirms isolation again
    Then 0 contacts should exist
