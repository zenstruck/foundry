Feature: Test

  Scenario: View homepage
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"

  Scenario: Can persist entities
    # Can name entities
    Given a contact A is created
    # Can create unnamed entities
    And a contact is created
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"
    # todo plurialize
    Then 2 contact should exist

  Scenario: Can visit pages twice and still access to EM
    Given a contact is created
    When I am on "/"
    Then I should see "Hello World"
    When I am on "/"
    Then I should see "Hello World"
    Then 1 contact should exist

  Scenario Outline: Persist entity
    Given a contact is created
    When I am on "/"
    Then the response status code should be 200
    Then I should see "<data>"
    Then 1 contact should exist

    Examples:
      | data |
      | Hello |
      | World |

  Scenario: Can create entity with properties via PyTable
    Given a contact A is created with properties
      | name     |
      | John Doe |
    Then 1 contact should exist
    Then contact A should have properties
      | name     |
      | John Doe |
