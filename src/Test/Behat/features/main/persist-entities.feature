Feature: Test persisting entities

  Scenario: View homepage
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"

  Scenario: Can persist entities
    # Can name entities
    Given there is a contact A
    # Can create unnamed entities
    And there is a contact
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"
    Then 2 contacts should exist

  Scenario: Can visit pages twice and still access to EM
    Given there is a contact
    When I am on "/"
    Then I should see "Hello World"
    When I am on "/"
    Then I should see "Hello World"
    Then 1 contact should exist

  Scenario Outline: Persist entity
    Given there is a contact
    When I am on "/"
    Then the response status code should be 200
    Then I should see "<data>"
    Then 1 contact should exist

    Examples:
      | data  |
      | Hello |
      | World |

  Scenario: Can access last created entity ID
    Given there is a "generic entity" "the object" with
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
    Given there is a "generic entity" "the object" with
      | prop1 |
      | foo   |
    And there is a contact
    When I am on "/orm/update/<lastId(generic entity)>/bar"
    Then the response status code should be 200
    Then "generic entity" "the object" should have properties
      | prop1 |
      | bar   |

  Scenario: Throws if last id is not found (!)
    When I am on "/orm/update/<lastId(generic entity)>/bar"
    Then an "InvalidArgumentException" exception should be thrown containing message "No object of type \"generic entity\" found"

  Scenario: Can access an ID from reference
    Given there is a "generic entity" "the object" with
      | prop1 |
      | foo   |
    When I am on "/orm/update/<id(generic entity, the object)>/bar"
    Then the response status code should be 200
    Then "generic entity" "the object" should have properties
      | prop1 |
      | bar   |

  Scenario: Throws if the reference is not found (!)
    When I am on "/orm/update/<id(generic entity, the object)>/bar"
    Then an "ObjectNotFound" exception should be thrown containing message "Object \"generic entity the object\" was not found"
