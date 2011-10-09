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
    Given An additional feature was loaded outside phabric
    And The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    When The following events are updated
    | Name  | Date             | Venue                   | Desc             |
    | PHPNW | 08/10/2011 10:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 27/02/2012 10:00 | London Business Center  | Quite good conf. |
    Then I should see the following records
    | name  | datetime            | venue                   | description      |
    | PHPNW | 2011-10-08 10:00:00 | Ramada Hotel MANCHESTER | An awesome conf! |
    | PHPUK | 2012-02-27 10:00:00 | London Business Center  | Quite good conf. |
    When I reset Phabric
    Then the "event" table should have 1 record




    