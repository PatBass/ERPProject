Feature: Run monthly payments for subscriptions
    Scenario: Subscription without credit card and alias
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Exception : No valid alias or card available" in the command output

    Scenario: Subscription with valid credit card but no alias
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Payment succeed" in the command output
        And "Julien" should have a payment alias on "mon-amour-voyance"

    Scenario: Subscription with invalid credit card but no alias
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "invalid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Payment #(\d+) failed" in the command output
        And "Julien" should not have a payment alias on "mon-amour-voyance"

    Scenario: Subscription payment not ready
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "+1 day" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should not see "BEHAT, Julien" in the command output

    Scenario Outline: Next subscription payment
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "<subscriptionDate>" with last payment at "<lastPaymentDate>" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Payment succeed" in the command output

        Examples:
            | subscriptionDate    | lastPaymentDate              |
            | -1 month, -15 day   | -1 month, -10 day            |
            | -1 month, -15 day   | -1 month, +10 day            |

    Scenario: Subscription with cbi card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "cbi" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Exception : No valid alias or card available" in the command output

    Scenario: Subscription with expired card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "expired" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> Exception : No valid alias or card available" in the command output

    Scenario: Subscription with invalid card data
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "invalidCardData" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> KGC\\PaymentBundle\\Exception\\Payment\\InvalidCardDataException : Invalid card data" in the command output

    Scenario: Subscription with exception card
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "exception" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "-5 minute" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien([^\n]+)\n\s+-> KGC\\PaymentBundle\\Exception\\Payment\\PaymentFailedException : Card exception thrown by gateway" in the command output

    Scenario Outline: Not eligible subscription payments
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "<subscriptionDate>" with last payment at "<lastPaymentDate>" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should not see "BEHAT, Julien" in the command output

        Examples:
            | subscriptionDate    | lastPaymentDate              |
            | -1 month, -15 day   | -1 month, +20 day            |
            | -15 day             | -14 day                      |

    Scenario Outline: Disabled subscription payments
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "<subscriptionDate>" disabled at "<disabledDate>" with last payment at "<lastSubPaymentDate>" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should not see "BEHAT, Julien" in the command output

                Examples:
            | subscriptionDate | disabledDate     | lastSubPaymentDate |
            | -5 minute        | now              |                    |
            | -1 month, -1 day | -2 day           | -1 hour            |
            | -2 month, -1 day | -1 month, -2 day | -1 month           |


    Scenario Outline: Last disabled subscription payment
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "<subscriptionDate>" disabled at "<disabledDate>" with last payment at "<lastSubPaymentDate>" on "mon-amour-voyance"
        When I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien" in the command output

        Examples:
            | subscriptionDate    | disabledDate      | lastSubPaymentDate  |
            | -1 month, -3 day    | now               | -1 month, -1 day    |
            | -1 month, -3 day    | now               | -5 day              |
            | -1 month, -3 day    | +1 month -4minute | -2 month, -1 day    |

    Scenario Outline: Allowed subscription payments after failed one
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "2016-01-15" with successful payment at "2016-01-15 01:00" and failed payment at "<failedPaymentDate>" on "mon-amour-voyance"
        When Subscription batch has current date set at "<currentDate>"
        And I run command "chat:subscription:generatePayments"
        Then I should see "BEHAT, Julien" in the command output

        Examples:
            | failedPaymentDate | currentDate         |
            |                   | 2016-02-15 01:00    |
            | 2016-02-15 01:00  | 2016-02-25 01:00    |
            | 2016-02-25 01:00  | 2016-02-29 01:00:00 |
            | 2016-02-29 01:00  | 2016-03-05 01:00:00 |
            | 2016-03-05 01:00  | 2016-03-10 01:00:00 |
            | 2016-03-10 01:00  | 2016-03-30 01:00:00 |
            | 2016-03-30 01:00  | 2016-04-05 01:00:00 |
            | 2016-04-05 01:00  | 2016-04-10 01:00:00 |
            | 2016-04-10 01:00  | 2016-04-30 01:00:00 |
            | 2016-04-30 01:00  | 2016-05-05 01:00:00 |
            | 2016-04-05 01:00  | 2016-05-10 01:00:00 |

    Scenario Outline: Not allowed subscription payments after failed one
        Given The following logged people exist:
            | type | username | password | website |
            | client | Julien | martinmatin | mon-amour-voyance |
        And "Julien" has a "valid" credit card on "mon-amour-voyance"
        And "Julien" has a subscription starting at "2016-01-15" with successful payment at "2016-01-15 01:00" and failed payment at "<failedPaymentDate>" on "mon-amour-voyance"
        When Subscription batch has current date set at "<currentDate>"
        And I run command "chat:subscription:generatePayments"
        Then I should not see "BEHAT, Julien" in the command output

        Examples:
            | failedPaymentDate | currentDate         |
            |                   | 2016-02-14 01:00:00 |
            | 2016-02-15 01:00  | 2016-02-24 01:00:00 |
            | 2016-02-25 01:00  | 2016-02-28 01:00:00 |
            | 2016-02-29 01:00  | 2016-03-04 01:00:00 |
            | 2016-03-05 01:00  | 2016-03-05 23:00:00 |
            | 2016-03-06 01:00  | 2016-03-09 01:00:00 |
            | 2016-03-10 01:00  | 2016-03-29 01:00:00 |
            | 2016-03-30 01:00  | 2016-04-04 01:00:00 |
            | 2016-04-05 01:00  | 2016-04-09 01:00:00 |
            | 2016-04-10 01:00  | 2016-04-29 01:00:00 |