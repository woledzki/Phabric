Feature: Phabric Example
     As a Phabric user I want to add some rows to the database. 
     I want to add related data. Numerous child entities should be able
     to map to a single parent. Ie - A one to many relationship.

Scenario: 
    Given the following sessions exist
    | Session Code | name                  | time  | description                               |
    | BDD          | BDD with behat        | 12:50 | Test driven behaviour development is cool |
    | CI           | Continous Integration | 13:30 | Integrate this!                           |
    And the following attendees exist
    | name                  |
    | Jack The Lad          |
    | Simple Simon          |
    | Peter Pan             |
    And the following votes exist
    | Attendee     | Session Code | Vote | 
    | Jack The Lad | BDD          | UP   |
    | Simple Simon | BDD          | UP   |
    | Peter Pan    | BDD          | UP   |
    | Jack The Lad | CI           | UP   |
    | Simple Simon | CI           | UP   |
    | Peter Pan    | CI           | DOWN |
    Then the session "BDD" should have a score of 3
    And the session "CI" should have a score of 1



    