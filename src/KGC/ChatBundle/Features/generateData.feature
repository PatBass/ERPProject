@ignore
Feature: Generate conversation data

    Scenario: Generate conversation data
        Given The following logged people exist:
            | type | sexe | username | email | password | website | chat_type |
            | psychic | woman | Spywoman | | jamesbond007 | | question |
            | client | | Julie | behattest-julienne@behattest.fr | martinmatin | anastazia | |
            | client | | Julienne | behattest-julienne@behattest.fr | martinmatin | mon-amour-voyance | |

        Given "Julie" enter a conversation with "Spywoman" on "anastazia"
        And With "Julie", I choose a "standard" formula rate on "anastazia" with "valid" card
        When "Julie" receive an answer, psychic decrements a question
        Then "Julie" send a message "Hello Spyboy !"

        Given "Julienne" enter a conversation with "Spywoman" on "mon-amour-voyance"
        And With "Julienne", I choose a "standard" formula rate on "mon-amour-voyance" with "valid" card
        When "Julienne" receive an answer, psychic decrements a question
        Then "Julienne" send a message "Hello Spyboy !"

        And No "element" clean