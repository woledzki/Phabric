Feature: Phabric Example
     As a Phabric user I want to be able to update entries previously inserted 
    by Phabric.

Scenario: A basic update is performed
    Given The following events exist
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


    