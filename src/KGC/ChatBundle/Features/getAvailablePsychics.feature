Feature: Get available virtual psychics
    In order to know who to contact
    As a client
    I need to be able to know who is available

    Scenario: Get available virtual psychics
        Given The following logged people exist:
            | type | username | password | website |
            | client | Marc | martinmatin | univers-de-luxe |

        Then With "Marc", I call authenticated url "/client/get-available-psychics"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"