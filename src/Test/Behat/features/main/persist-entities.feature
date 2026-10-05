Feature: Test persisting entities

  Scenario: View homepage
    When I am on "/"
    Then the response status code should be 200
    Then I should see "Hello World"

  Scenario: Can persist entities
    # Can name entities
    Given there is a contact named A
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
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    And there is a contact
    When I am on "/orm/update/<foundry:lastId(generic entity)>/bar"
    Then the response status code should be 200
    Then "generic entity" named "the object" should have properties:
      | prop1 |
      | bar   |

  Scenario: Throws if last id is not found (!)
    When I resolve the last id for "generic entity"
    Then a "RuntimeException" exception should be thrown containing message "objects persisted"

  Scenario: Can access an ID from reference
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    When I am on "/orm/update/<foundry:id(generic entity, the object)>/bar"
    Then the response status code should be 200
    Then "generic entity" named "the object" should have properties:
      | prop1 |
      | bar   |

  Scenario: Throws if the reference is not found (!)
    When I resolve the id of "generic entity" named "the object"
    Then an "ObjectNotFound" exception should be thrown containing message "Object \"generic entity the object\" was not found"

  Scenario: lastId for a type also sees unnamed entities
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    And there is a "generic entity" with:
      | prop1 |
      | foo   |
    When I am on "/orm/update/<foundry:lastId(generic entity)>/bar"
    Then the response status code should be 200
    # the named entity was created first: the placeholder resolved to the unnamed one
    Then "generic entity" named "the object" should have properties:
      | prop1 |
      | foo   |

  Scenario: Can combine several id placeholders in one argument
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    # an unresolved first placeholder would not even match the /orm/update/{id} route;
    # the exact multi-substitution is covered by ObjectRegistryTest
    When I am on "/orm/update/<foundry:id(generic entity, the object)>/bar-<foundry:lastId(generic entity)>"
    Then the response status code should be 200

  Scenario Outline: Foundry placeholders coexist with scenario outline tokens
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    When I am on "/orm/update/<foundry:id(generic entity, the object)>/<newValue>"
    Then the response status code should be 200
    Then "generic entity" named "the object" should have properties:
      | prop1      |
      | <newValue> |

    Examples:
      | newValue |
      | bar      |
      | baz      |

  Scenario: Assertions are database-backed
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    Then "generic entity" named "the object" should exist
    When I am on "/orm/delete/<foundry:id(generic entity, the object)>"
    Then the response status code should be 200
    Then "generic entity" named "the object" should not exist

  Scenario: should exist fails when the app deleted the row (!)
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    When I am on "/orm/delete/<foundry:id(generic entity, the object)>"
    Then "generic entity" named "the object" should exist
    Then an "AssertionFailed" exception should be thrown containing message "does not exist in the database although it should"

  Scenario: lastId sees entities created by the application itself
    Given there is a "generic entity" with:
      | prop1 |
      | foo   |
    When I am on "/orm/create/created-by-the-app"
    Then the response status code should be 200
    Then 2 "generic entities" should exist
    Then the "generic entity" with id "<foundry:lastId(generic entity)>" should have properties:
      | prop1              |
      | created-by-the-app |

  Scenario: Can assert existence by id
    Given there is a "generic entity" named "the object" with:
      | prop1 |
      | foo   |
    Then the "generic entity" with id "<foundry:id(generic entity, the object)>" should exist
    Then the "generic entity" with id 0 should not exist

  Scenario: Assertion by id fails when the row does not exist (!)
    Then the "generic entity" with id 0 should exist
    Then a "RuntimeException" exception should be thrown containing message "object found for"

  Scenario: Can assert existence with properties
    Given there is a "generic entity" with:
      | prop1 |
      | foo   |
    Then a "generic entity" should exist with:
      | prop1 |
      | foo   |
    Then no "generic entity" should exist with:
      | prop1 |
      | bar   |

  Scenario: Property-based assertions see entities created by the application itself
    When I am on "/orm/create/created-by-the-app"
    Then the response status code should be 200
    Then a "generic entity" should exist with:
      | prop1              |
      | created-by-the-app |

  Scenario: Property-based assertion fails when no row matches (!)
    Then a "generic entity" should exist with:
      | prop1   |
      | missing |
    Then an "AssertionFailed" exception should be thrown containing message "No \"generic entity\" matching the given properties exists in the database"
