Feature: Test @withFixture tag

  @withFixture(behat-contacts)
  Scenario: Load behat-contacts fixture with @withFixture tag
    Then 1 contact should exist

  Scenario: Ensure DB is fresh
    Then 0 contact should exist

  @withFixture(behat-contacts)
  Scenario Outline: Works with scenario outline
    When I am on "/"
    Then the response status code should be 200
    Then I should see "<data>"
    Then 1 contact should exist

    Examples:
      | data  |
      | Hello |
      | World |

  @withFixture(behat-contacts)
  Scenario: Can access entities from fixture
    Then 1 contact should exist
    Then contact "john-doe" should have properties
      | name     |
      | John Doe |

  @withFixture(behat-category)
  Scenario: Can use entities from fixture in another entity
    Given there is a contact "jane-doe" with
      | name     | category         |
      | Jane Doe | category fixture |
    Then 1 contact should exist
    Then contact "jane-doe" should have properties
      | name     | category         |
      | Jane Doe | category fixture |

  @withFixture(behat-category) @withFixture(behat-contacts)
  Scenario: Can load multiple fixtures
    Then 1 contact should exist
    Then 2 categories should exist

  @withFixture(behat-stories)
  Scenario: Can load grouped fixtures
    Then 1 contact should exist
    Then 2 categories should exist
