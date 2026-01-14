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

#  @withFixture(behat-categories)
#  Scenario: Load behat-categories fixture with @withFixture tag
#    Given I am on "/"
#    Then the response status code should be 200
#    # BehatCategoriesStory creates 2 categories
#    Then 2 categories should exist
#    # No contacts should be created
#    Then 0 contacts should exist
#
#  @withFixture(behat-group)
#  Scenario: Load fixture group with @withFixture tag
#    Given I am on "/"
#    Then the response status code should be 200
#    # behat-group contains both behat-contacts and behat-categories
#    Then 3 contacts should exist
#    Then 2 categories should exist
#
#  @withFixture(behat-generic-entities)
#  Scenario: Load fixture with specific data
#    # Test if the fixture was loaded before any HTTP request
#    Then 1 "generic entity" should exist
#    Given I am on "/"
#    Then the response status code should be 200
#    # Test if the fixture is still present after HTTP request
#    Then 1 "generic entity" should exist
#
#  @withFixture(behat-contacts)
#  Scenario: Fixture loaded before scenario, additional data created during scenario
#    # Fixture already loaded (3 contacts)
#    Then 3 contacts should exist
#    # Create additional contact during scenario
#    Given a contact is created with properties
#      | name           |
#      | Manual Contact |
#    # Now we should have 4 contacts
#    Then 4 contacts should exist
#
#  @withFixture(behat-generic-entities)
#  Scenario: Load fixture multiple times in different scenarios
#    Given I am on "/"
#    Then the response status code should be 200
#    # Generic entity from fixture should be present
#    Then 1 "generic entity" should exist
#
#  @withFixture(behat-generic-entities)
#  Scenario: Each scenario gets fresh database with fixture reloaded
#    Given I am on "/"
#    Then the response status code should be 200
#    # Each scenario gets a fresh database with the fixture reloaded
#    Then 1 "generic entity" should exist
