Feature: Reopen conversation
    In order to converse
    As a client
    I need to be able to reopen a conversation in case psychic did not quit

    Scenario: Reopen a conversation
        Given The following logged people exist:
            | type | username | password | chat_type | website |
            | psychic | Spyboy | jamesbond007 | minute | |
            | client | Marc | martinmatin | | voyance-par-tchat |

        When With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"

        Then "Marc" leave this conversation
        #And this room should be "closed"

        Then "Marc" reopen this conversation
        Then the response should be json object with status "OK"
        #And this room should be "open"

    Scenario: Fail to reopen conversation if psychic did quit
        Given The following logged people exist:
            | type | username | password | chat_type | website |
            | psychic | Spyboy | jamesbond007 | minute | |
            | client | Marc | martinmatin | | voyance-par-tchat |

        When With "Marc", I choose a "discovery" formula rate on "voyance-par-tchat"
        Then With "Marc", I call authenticated url "/client/get-available-psychics/voyance-par-tchat"
        Then the response should be json object with status "OK"
        And the object "websites" should be formated as "websites.json"

        Then With "Marc", I ask a conversation with a random psychic available
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"
        And the response should be formated as "answer.json"

        Then "Marc" leave this conversation
        #And this room should be "closed"

        Then "Spyboy" leave this conversation

        Then "Marc" reopen this conversation
        Then the response should be json object with status "KO"
        And the JSON field "message" should be equal to "No more psychic active in this room."
        #And this room should be "open"