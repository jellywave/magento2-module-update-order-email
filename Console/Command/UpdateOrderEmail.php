<?php
/**
 * Jellywave_UpdateOrderEmail extension
 *
 * @category Jellywave
 * @package Jellywave_UpdateOrderEmail
 * @author Christopher Diaper <chris@jellywave.com>
 * @copyright Copyright (c) 2020 Jellywave
 */
/*
 * https://devdocs.magento.com/guides/v2.4/extension-dev-guide/cli-cmds/cli-howto.html
 * https://symfony.com/doc/current/console/coloring.html
 */
declare(strict_types=1);

namespace Jellywave\UpdateOrderEmail\Console\Command;

use Magento\Sales\Api\OrderRepositoryInterface;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};

/**
 * Class UpdateOrderEmail
 * Use for manual updating email address on orders
 * @package Jellywave\UpdateOrderEmail\Console\Command
 */
class UpdateOrderEmail extends Command
{

    const INCREMENT_ID_ARGUMENT = "increment_id";
    const EMAIL_ARGUMENT = "email";

    const INCREMENT_ID_ARGUMENT_SHORT = "i";
    const EMAIL_ARGUMENT_SHORT = "e";

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('sales:order:update-email');
        $this->setDescription('Update the email address on an existing order.');
        $this->addOption(
            self::INCREMENT_ID_ARGUMENT,
            self::INCREMENT_ID_ARGUMENT_SHORT,
            InputOption::VALUE_OPTIONAL,
            'Increment ID Search'
        );
        $this->addOption(
            self::EMAIL_ARGUMENT,
            self::EMAIL_ARGUMENT_SHORT,
            InputOption::VALUE_OPTIONAL,
            'Email Address Search'
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $incrementId = $input->getOption(self::INCREMENT_ID_ARGUMENT);
        $email = $input->getOption(self::EMAIL_ARGUMENT);

        if($incrementId || $email) {
            $orders = [];
            $output->writeln('Finished');
        } else {
            $output->writeln('<error>Please specify an increment_id or email</error>');
        }

    }

}