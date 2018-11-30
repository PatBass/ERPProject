<?php

namespace KGC\ChatBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionCommand extends ContainerAwareCommand
{
    protected $checkedUsers = null;

    protected function configure()
    {
        $this
            ->setName('chat:subscription:generatePayments')
            ->setDescription('Generate monthly payments for chat subscriptions')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Display ready subscriptions without generating payments');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batch = $this->getContainer()->get('kgc.chat.subscription.batch');

        $batch->setOutput($output);
        $batch->process($input->getOption('dry-run'));
    }

    public function setCheckedUsers(array $checkedUsers)
    {
        $this->getContainer()->get('kgc.chat.subscription.batch')->setCheckedUsers($checkedUsers);
    }
}
