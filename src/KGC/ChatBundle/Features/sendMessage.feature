Feature: Join a room and send a message as a client
    In order to converse in room
    As a client
    I need to be able to send a message, be decredited with time

    Scenario: Join and send a message
        Given The following logged people exist:
            | type | username | password | website |
            | psychic | Spyboy | jamesbond007 | |
            | client | Julien | martinmatin | voyance-par-tchat |

        And "Julien" enter a conversation with "Spyboy" on "voyance-par-tchat"


        Then Wait 5 seconds

        Then "Julien" send a message "Hello Spyboy !"
        Then the response should be json object with status "OK"
        And the object "chat_message" should be formated as "message.json"
        And the object "room" should be formated as "room.json"

        Then Wait 5 seconds

        Then "Spyboy" send a message "Hello Julien !"
        Then the response should be json object with status "OK"
        And the object "chat_message" should be formated as "message.json"
        And the object "room" should be formated as "room.json"
        And the remaining time should be 10 seconds less than original formula rate

        Then Wait 5 seconds

        Then "Spyboy" send a message "Hello Julien !"
        Then the response should be json object with status "OK"
        And the object "chat_message" should be formated as "message.json"
        And the object "room" should be formated as "room.json"
        And the remaining time should be 15 seconds less than original formula rate

        Then "Julien" send a way too long message for this conversation
        Then the response should be json object with status "KO"

    Scenario: Send messages consuming two different payments
        Given The following logged people exist:
            | type | username | password | website |
            | psychic | Spyboy | jamesbond007 | |
            | client | Julien | martinmatin | voyance-par-tchat |
        Then "Julien" enter a conversation with "Spyboy" on "voyance-par-tchat"
        And "Julien" has almost consumed a free offer on this conversation

        Then Wait 5 seconds

        And "Julien" send a message "Hello Spyboy !"
        Then the response should be json object with status "OK"
        And the object "chat_message" should be formated as "message.json"
        And the object "room" should be formated as "room.json"
        And the remaining time should be 0 seconds less than original formula rate

        Then Wait 5 seconds

        And "Spyboy" leave this conversation