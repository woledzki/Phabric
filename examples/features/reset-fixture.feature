Feature: Phabric Example
     As a Phabric user I want to be able to reset all previous inserts and 
     updates on a fixture.

Scenario: Data that has only been inserted by Phabric is reset correctly
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    When I select all records from the event table
    Then I should see the following records
    | name  | datetime            | venue                  | description      |
    | PHPNW | 2011-10-08 09:00:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 2012-02-27 09:00:00 | London Business Center | Quite good conf. |
    When I reset Phabric
    Then there sould be not data in the "event" table


Scenario: Data that has been inserted and updated by phabric is reset correctly
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    And The following events are updated
    | Name  | Date             | Venue                   | Desc             |
    | PHPNW | 08/10/2011 10:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 27/02/2012 10:00 | London Business Center  | Quite good conf. |
    When I select all records from the event table    
    Then I should see the following records
    | name  | datetime            | venue                   | description      |
    | PHPNW | 2011-10-08 10:00:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 2012-02-27 10:00:00 | London Business Center  | Quite good conf. |
    When I reset Phabric
    Then there sould be not data in the "event" table

Scenario: Existing data is not removed by a reset
    Given data was loaded independantley of Phabric
    And The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    And The following events are updated
    | Name  | Date             | Venue                   | Desc             |
    | PHPNW | 08/10/2011 10:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 27/02/2012 10:00 | London Business Center  | Quite good conf. |
    When I select all records from the event table       
    Then I should see the following records
    | name  | datetime            | venue                   | description      |
    | PBC11 | 2011-10-28 09:00:00 | Barcellona              | HOT conf         |
    | PHPNW | 2011-10-08 10:00:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 2012-02-27 10:00:00 | London Business Center  | Quite good conf. |
    When I reset Phabric
    And I select all records from the event table   
    Then I should see the following records
    | name  | datetime            | venue                  | description      |
    | PBC11 | 2011-10-28 09:00:00 | Barcellona             | HOT conf         |

Scenario: Existing data can be updated using Phabric and reset to it's original 
          state when Phabric reset is used.
    Given data was loaded independantley of Phabric
    And The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    And The following events are updated
    | Name  | Date             | Venue                   | Desc             |
    | PHPNW | 08/10/2011 10:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 27/02/2012 10:00 | London Business Center  | Quite good conf. |
    When I select all records from the event table       
    Then I should see the following records
    | name  | datetime            | venue                   | description      |
    | PBC11 | 2011-10-28 09:00:00 | Barcellona              | HOT conf         |
    | PHPNW | 2011-10-08 10:00:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 2012-02-27 10:00:00 | London Business Center  | Quite good conf. |
    When I use phabric to update data not managed by phabric
    | name  | description      |
    | PBC11 | Cool conf!       |
    When I reset Phabric
    And I select all records from the event table   
    Then I should see the following records
    | name  | datetime            | venue                  | description      |
    | PBC11 | 2011-10-28 09:00:00 | Barcellona             | HOT conf         |
