Feature: Test persisting entities

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
    Then 2 contacts should exist

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
      | data  |
      | Hello |
      | World |

  Scenario: Can access last created entity ID
    Given a "generic entity" "the object" is created with properties
      | prop1 |
      | foo   |
    When I am on "/orm/update/<lastId>/bar"
    Then the response status code should be 200
    Then "generic entity" "the object" should have properties
      | prop1 |
      | bar   |

  Scenario: Throws if last id is not found (!)
    When I am on "/orm/update/<lastId>/bar"
    Then an "RuntimeException" exception should be thrown containing message "No last id found"

  Scenario: Can access last created entity ID
    Given a "generic entity" "the object" is created with properties
      | prop1 |
      | foo   |
    And a contact is created
    When I am on "/orm/update/<lastId(generic entity)>/bar"
    Then the response status code should be 200
    Then "generic entity" "the object" should have properties
      | prop1 |
      | bar   |

  Scenario: Throws if last id is not found (!)
    When I am on "/orm/update/<lastId(generic entity)>/bar"
    Then an "InvalidArgumentException" exception should be thrown containing message "No object of type \"generic entity\" found"
