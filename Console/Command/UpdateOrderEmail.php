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
 * https://symfony.com/doc/current/components/console/helpers/questionhelper.html
 * Magento_Customer/Model/CustomerRegistry.php
 */
declare(strict_types=1);

namespace Jellywave\UpdateOrderEmail\Console\Command;

use Magento\Store\Model\StoreManagerInterface;

use Magento\Framework\Api\{
    SearchCriteriaBuilder,
    FilterBuilder
};
use Magento\Sales\Api\{
    Data\OrderInterface,
    OrderRepositoryInterface
};

use Magento\Customer\Model\{
    Customer,
    CustomerFactory
};

use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Question\Question,
    Question\ConfirmationQuestion
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $salesOrderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;


    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    private $websiteId = 1;

    /**
     * RetailSystemSync constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $salesOrderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        OrderRepositoryInterface $salesOrderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        string $name = null
    ) {
        $this->salesOrderRepository = $salesOrderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name);
    }

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
        $customerUpdate = false;
        $orders = [];

        if($this->storeManager->isSingleStoreMode() || $this->storeManager->hasSingleStore()){
            $this->websiteId = (int)$this->storeManager->getDefaultStoreView()->getWebsiteId();
        } else {
            $websites = $this->storeManager->getWebsites();
            $websitesIds = [];
            $output->writeln("<info>Please select a Website scope : </info>");
            /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
            foreach($websites as $website) {
                $websitesIds[] = $website->getId();
                $output->writeln("<info>{$website->getId()} : {$website->getName()}</info>");
            }

            /** @var \Symfony\Component\Console\Question\Question $question */
            $question = new Question('Please enter website ID : ');

            $helper = $this->getHelper('question');
            $this->websiteId = $helper->ask($input, $output, $question);
            if(!in_array($this->websiteId, $websitesIds)){
                throw new \Exception("Invalid Website ID");
            }
        }

        if($incrementId) {
            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            $order = $this->getSalesOrderByIncrementId((int) $incrementId);

            $output->writeln("<info>Order #{$order->getIncrementId()} current email address : {$order->getCustomerEmail()}</info>");
            $orders[] = $order;
        } else if($email) {
            $orders = $this->getSalesOrderByEmail($email);

            $output->writeln("<info>Current orders with email address : {$email}</info>");

            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            foreach($orders as $order) {
                $output->writeln("<info>#{$order->getIncrementId()}</info>");
            }

        } else {
            throw new \Exception("No increment_id or email specified");
        }

        $output->writeln("");

        /** @var \Symfony\Component\Console\Question\Question $question */
        $question = new Question('Please enter the new email address : ');

        $helper = $this->getHelper('question');
        $newemail = $helper->ask($input, $output, $question);

        /* ToDo Replace with Magento validator */
        if (!filter_var($newemail, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email format");
        }

        $customerId = $this->validateCustomer($newemail, $this->storeId);
        if($customerId) {

            /** @var \Symfony\Component\Console\Question\Question $question */
            $question = new ConfirmationQuestion('Change customer association? [y|n] : ', false);

            if ($helper->ask($input, $output, $question)) {
                $customerUpdate = true;
            }
        }

        $output->writeln("<comment>Set orders to email {$newemail}</comment>\n");
        /* Confirm action */
        /** @var \Symfony\Component\Console\Question\Question $question */
        $question = new ConfirmationQuestion('Continue with update? [y|n] : ', false);

        if ($helper->ask($input, $output, $question)) {

            try {
                /** @var \Magento\Sales\Api\Data\OrderInterface $order */
                foreach($orders as $order) {

                    $order->setCustomerEmail($newemail);
                    if($customerUpdate){
                        $order->setCustomerId($customerId);
                        $order->setCustomerIsGuest(0);
                    }
                    $this->salesOrderRepository->save($order);

                    $output->writeln("<info>Updated #{$order->getIncrementId()}</info>");
                }

            } catch (Exception $e) {
                throw new \Exception($e->getMessage());
            }
        } else {
            $output->writeln("\nCancelled\n");
        }

    }

    /**
     * Resolve users email to a customer ID
     *
     * @param string $customerEmail Customers email address
     * @param string|null $websiteId Optional website ID, if not set, will use the current websiteId
     * @return int
     */
    private function validateCustomer(string $customerEmail, $websiteId = null): int
    {
        /** @var Customer $customer */
        $customer = $this->customerFactory->create();

        if (isset($websiteId)) {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($customerEmail);
        return (int)$customer->getId();
    }

    /**
     * Retrieves sales order by increment ID
     *
     * @param int $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Exception
     */
    private function getSalesOrderByIncrementId(int $incrementId): OrderInterface
    {
        /** @var \Magento\Framework\Api\Filter $filter */
        $filter = $this->filterBuilder->setField(OrderInterface::INCREMENT_ID)
            ->setConditionType('eq')
            ->setValue($incrementId)
            ->create();
        $this->searchCriteriaBuilder->addFilters([$filter]);

        /** @var \Magento\Sales\Api\Data\OrderSearchResultInterface $list */
        $list = $this->salesOrderRepository->getList(
            $this->searchCriteriaBuilder->create()
        );

        $firstItemIndex = array_key_first($list->getItems());
        if (!$firstItemIndex) {
            throw new \Exception("Couldn't find the order with ID #{$incrementId}");
        }

        return $list->getItems()[$firstItemIndex];
    }

    /**
     * Retrieves sales order by increment ID
     *
     * @param string $email
     * @return array
     * @throws \Exception
     */
    private function getSalesOrderByEmail(string $email): array
    {
        /** @var \Magento\Framework\Api\Filter $filter */
        $filter = $this->filterBuilder->setField(OrderInterface::CUSTOMER_EMAIL)
            ->setConditionType('eq')
            ->setValue($email)
            ->create();
        $this->searchCriteriaBuilder->addFilters([$filter]);

        /** @var \Magento\Sales\Api\Data\OrderSearchResultInterface $list */
        $list = $this->salesOrderRepository->getList(
            $this->searchCriteriaBuilder->create()
        );

        $firstItemIndex = array_key_first($list->getItems());
        if (!$firstItemIndex) {
            throw new \Exception("Couldn't find the order with email {$email}");
        }

        return $list->getItems();
    }


}