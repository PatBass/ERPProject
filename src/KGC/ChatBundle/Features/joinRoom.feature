Feature: Join and leave a room
    In order to converse in room
    As a client
    I need to be able to join and leave it when desired

    Scenario: Join and leave a room
        Given The following logged people exist:
            | type | username | password | website | chat_type |
            | psychic | Spyboy | jamesbond007 | | 0 |
            | client | Julien | martinmatin | voyance-par-tchat | |

        Given With "Julien", I choose a "random" formula rate on "voyance-par-tchat"
        Then the response should be json object with status "OK"

        Given "Julien" ask a conversation with "Spyboy" on "voyance-par-tchat"

        Then With "Spyboy", I "accept" to join the conversation
        Then the response should be json object with status "OK"

        # Exit room test
        When With "Julien", I leave the previous created room
        Then the response should be json object with status "OK"

        When With "Spyboy", I leave the previous created room
        Then the response should be json object with status "OK"

    Scenario: If there is no psychic left, the room should be closed
        Given The following logged people exist:
            | type | username | password | website | chat_type |
            | psychic | Spyboy | jamesbond007 | | 0 |
            | client | Julien | martinmatin | voyance-par-tchat | |

        Given With "Julien", I choose a "random" formula rate on "voyance-par-tchat"
        Then the response should be json object with status "OK"

        Given "Julien" ask a conversation with "Spyboy" on "voyance-par-tchat"

        When With "Spyboy", I leave the previous created room
        Then the response should be json object with status "OK"
        And the JSON field "room_end_date" should be "int"

    Scenario: If there is no client left and the room is not started, the room should be closed
        Given The following logged people exist:
            | type | username | password | website | chat_type |
            | psychic | Spyboy | jamesbond007 | | 0 |
            | client | Julien | martinmatin | voyance-par-tchat | |

        Given With "Julien", I choose a "random" formula rate on "voyance-par-tchat"
        Then the response should be json object with status "OK"

        Given "Julien" ask a conversation with "Spyboy" on "voyance-par-tchat"

        When With "Julien", I leave the previous created room
        Then the response should be json object with status "OK"
        And the JSON field "room_end_date" should be "int"

