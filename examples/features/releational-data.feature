Feature: Phabric Example
     As a Phabric user I want to add some rows to the database. 
     I want to add related data. Numerous child entities should be able
     to map to a single parent. Ie - A one to many relationship.

Scenario: 
    Given the following sessions exist
    | Session Code | name                  | time  | description                               |
    | BDD1         | BDD with behat        | 12:50 | Test driven behaviour development is cool |
    | CI1          | Continous Integration | 13:30 | Integrate this!                           |
    And the following attendees exist
    | name                  |
    | Jack The Lad          |
    | Simple Simon          |
    | Peter Pan             |
    And the following votes exist
    | Atendee      | Session | Vote | 
    | Jack The Lad | BDD1    | UP   |
    | Simple Simon | BDD1    | UP   |
    | Peter Pan    | BDD1    | UP   |
    | Jack The Lad | CI1     | UP   |
    | Simple Simon | CI1     | DOWN |
    | Peter Pan    | CI1     | DOWN |
    Then the session BDD1 should have a score of 3
    And the session CI1 should have a score of -1



    