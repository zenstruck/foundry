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

  Scenario: Can create entity with properties via PyTable
    Given a contact A is created with properties
      | name     |
      | John Doe |
    Then 1 contact should exist
    Then contact A should have properties
      | name     |
      | John Doe |

  Scenario: Can create multiple entities via PyTable
    Given contacts are created with properties
      | _ref | name     |
      | A    | John Doe |
      | B    | Jane Doe |
    Then 2 contacts should exist
    Then contact A should have properties
      | name     |
      | John Doe |
    Then contact B should have properties
      | name     |
      | Jane Doe |

  Scenario: Can reference another object
    Given a category MyCategory is created
    And an address "the address" is created
    And a contact A is created with properties
      | name     | category                    | address                     |
      | John Doe | <ref(category, MyCategory)> | <ref(address, the address)> |
    When I am on "/"
    Then contact A should have properties
      | name     | category                    | address                     |
      | John Doe | <ref(category, MyCategory)> | <ref(address, the address)> |
    Then 1 contact should exist
    Then 1 category should exist
    Then 1 address should exist

  Scenario: Can access last created entity ID
    Given a "generic entity" "the object" is created with properties
      | prop1 |
      | foo   |
    When I am on "/orm/update/<lastId>/bar"
    Then the response status code should be 200
    Then "generic entity" "the object" should have properties
      | prop1 |
      | bar   |

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
