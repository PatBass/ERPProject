Feature: Ask conversation history
    In order to see my history
    As a client
    I need to be able to get conversations in where I am involved

    Scenario: Get conversations
        Given The following logged people exist:
            | type | username | password | website |
            | psychic | Spyboy | jamesbond007 | |
            | client | Julien | martinmatin | voyance-par-tchat |

        Given "Julien" enter a conversation with "Spyboy" on "voyance-par-tchat"


        Then "Julien" send a message "Hello Spyboy !"
        Then the response should be json object with status "OK"

        Then "Spyboy" send a message "Hello Julien !"
        Then the response should be json object with status "OK"

        Then With "Julien", I call authenticated url "/client/voyance-par-tchat/history/chat"
        Then the response should be json object with status "OK"
        And the object "conversations" should be formated as "conversations.json"
        And the object "conversations" should contains 1 elements