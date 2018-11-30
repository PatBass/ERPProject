Feature: Get rooms for a psychic
    In order to converse with clients
    As a psychic
    I need to be able to get my rooms when I load a page

    Scenario: Get rooms for a psychic
        Given The following logged people exist:
            | type | username | password | website |
            | psychic | Spyboy | jamesbond007 | |
            | client | Julien | martinmatin | voyance-par-tchat |

        Given "Julien" enter a conversation with "Spyboy" on "voyance-par-tchat"

        When With "Spyboy", I call authenticated url "/psychic/room/get"
        Then the response should be json object with status "OK"
        And the object "rooms" should be formated as "rooms.json"