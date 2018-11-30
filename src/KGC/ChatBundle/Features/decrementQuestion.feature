Feature: Decrement question as a psychic
    In order to chat in question's room
    As a psychic
    I need to be able to decrement question

    Scenario: Decrement question
        Given The following logged people exist:
            | type | sexe | username | password | website | chat_type |
            | psychic | woman | Spygirl | jamesbond007 | | question |
            | client | woman | Julien | martinmatin | mon-amour-voyance | |

        Given "Julien" enter a conversation with "Spygirl" on "mon-amour-voyance"

        When "Julien" receive an answer, psychic decrements a question
        Then the response should be json object with status "OK"
        And the object "room" should be formated as "room.json"
        And the remaining question should be 1 less than original formula rate

        Then With "Julien", I call authenticated url "/client/mon-amour-voyance/remaining-credit"
        Then the response should be json object with status "OK"
        And the object "credits_by_chat_type" should be formated as "credits_by_chat_type.json"
        And the object "credits_by_chat_type" should contains 1 elements