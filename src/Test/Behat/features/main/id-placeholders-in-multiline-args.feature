Feature: Resolving id placeholders in PyString and table arguments

  # The inline argument and the multiline one always carry DIFFERENT placeholders resolving
  # to the same value (id() reads the object registry, lastId() queries the database): equal
  # results prove both sides were actually resolved, not merely substituted the same way.
  Scenario: Id placeholders are resolved in PyString arguments
    Given there is a contact named A
    Then each line of the following text should equal "<foundry:lastId(contact)>":
      """
      <foundry:id(contact, A)>
      """
    And each line of the following text should equal "id=<foundry:id(contact, A)>":
      """
      id=<foundry:lastId(contact)>
      id=<foundry:lastId("contact")>
      """

  Scenario: Id placeholders are resolved in the table arguments of custom steps
    Given there is a contact named A
    Then all cells of the following table should equal "<foundry:lastId(contact)>":
      | <foundry:id(contact, A)> | <foundry:id("contact", "A")> |
    And all cells of the following table should equal "<foundry:id("contact", "A")>":
      | <foundry:lastId(contact)> | <foundry:lastId("contact")> |

  # A definition may declare its multiline argument optional (e.g. an HTTP step whose request
  # body is not always given). By-type transformations match on the parameter's TYPE, so they
  # also run when the step is called WITHOUT the multiline argument — they must pass null through.
  Scenario: Optional multiline arguments may be omitted
    Given there is a contact named A
    Then the value "<foundry:id(contact, A)>" should not contain an unresolved placeholder
    And no cell of the optional table should contain an unresolved placeholder for "<foundry:id(contact, A)>"
    And the value "<foundry:id(contact, A)>" should not contain an unresolved placeholder:
      """
      <foundry:lastId(contact)>
      """
    And no cell of the optional table should contain an unresolved placeholder for "<foundry:id(contact, A)>":
      | <foundry:lastId(contact)> |

  # The creation and assertion tables use different placeholders resolving to the same value:
  # if resolution silently stopped on either side, the literals would no longer match.
  Scenario: Id placeholders are resolved in the tables of the built-in steps
    Given there is a contact named A with:
      | name |
      | John |
    And there is a category named C with:
      | name                         |
      | cat-<foundry:id(contact, A)> |
    Then the category named C should have properties:
      | name                          |
      | cat-<foundry:lastId(contact)> |
    And a category should exist with:
      | name                          |
      | cat-<foundry:lastId(contact)> |
