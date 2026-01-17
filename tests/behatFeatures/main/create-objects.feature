Feature: Test object creation

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

  Scenario: Can reference another object with short syntax
    Given a category MyCategory is created
    And an address "the address" is created
    And a contact A is created with properties
      | name     | category   | address     |
      | John Doe | MyCategory | the address |
    When I am on "/"
    Then contact A should have properties
      | name     | category   | address     |
      | John Doe | MyCategory | the address |

  Scenario: Can reference object with date
    Given a "generic entity" "GE" is created with properties
      | prop1 | propInteger | date       | dateMutable | bool  | float |
      | foo   | 1           | 2026-01-01 | 2026-01-02  | false | 3.14  |
    When I am on "/"
    Then "generic entity" "GE" should have properties
      | prop1 | propInteger | date       | dateMutable | bool  | float |
      | foo   | 1           | 2026-01-01 | 2026-01-02  | false | 3.14  |

  Scenario: Can compare null
    Given a "generic entity" "GE" is created with properties
      | prop1 | bool |
      | foo  | null |
    When I am on "/"
    Then "generic entity" "GE" should have properties
      | prop1 | bool |
      | foo  | null |
