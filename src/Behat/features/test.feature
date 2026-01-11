Feature: Test

  Scenario: View homepage
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"

  Scenario: Can persist entity
    Given A contact is created
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"
    Then A contact should exist

  Scenario Outline: Persist entity
    Given A contact is created
    When I am on "/"
    Then the response status code should be 200
    Then I should see "<data>"
    Then A contact should exist

    Examples:
      | data |
      | Hello |
      | World |
