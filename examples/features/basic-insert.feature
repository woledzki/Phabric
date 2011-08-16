Feature: Phabric Example
     As a Phabric user I want to add some rows to the database. 
     I want to use nice colun headers in my Gherkin, tranform values from
     one format to another and make use of default values.

Scenario:
    Given The following events exist
    | Name  | Date             | Venue                  | Desc             |
    | PHPNW | 08/10/2011 09:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 27/02/2012 09:00 | London Business Center | Quite good conf. |
    When I select all records from the event table
    Then I should see the following records
    | name  | datetime            | venue                  | description      |
    | PHPNW | 2011-10-08 09:00:00 | Ramada Hotel           | An awesome conf! |
    | PHPUK | 2012-02-27 09:00:00 | London Business Center | Quite good conf. |


