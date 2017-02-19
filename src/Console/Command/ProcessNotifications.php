<?php
namespace Concrete\Package\QueueDemo\Src\Console\Command;

use Concrete\Core\Application\Application;
use Concrete\Core\Foundation\Queue\Queue;
use Symfony\Component\Console\Application as ConsoleApplication;
use Concrete\Core\User\UserInfoRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Concrete\Core\Support\Facade\Facade;
use Core;

class ProcessNotifications extends Command
{

    /**
     * @var resource
     */
    protected $fd;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var ConsoleApplication
     */
    protected $console;

    protected function configure()
    {
        $this
            ->setName('queue-demo:process-notifications')
            ->setDescription('Sends notifications')
            ->setHelp('Run the command and get notifications');
    }

    /**
     * Locks the process to ensure that we don't run the same process twice
     *
     * @return bool
     */
    protected function lock()
    {
        $result = false;
        $this->fd = @fopen(__FILE__, 'r');
        if ($this->fd) {
            if (@flock($this->fd, LOCK_EX | LOCK_NB)) {
                $result = true;
            } else {
                @fclose($this->fd);
                $this->fd = null;
            }
        }

        return $result;
    }

    /**
     * Unlocks the process
     */
    protected function unlock()
    {
        if ($this->fd) {
            @flock($this->fd, LOCK_UN);
            @fclose($this->fd);
            $this->fd = null;
        }
    }

    protected function sendNotifications()
    {
        $queue = Queue::get('queue-demo');

        /** @var UserInfoRepository $userInfo */
        $userInfo = $this->app->make(UserInfoRepository::class);

        /** @var \Concrete\Core\Mail\Service $mailService */
        $mailService = $this->app->make('mail');

        $count = 0;

        // get 10 messages from the queue and process them
        for (; ;) {
            $queueMessages = $queue->receive(10);
            if ($queueMessages->count() < 1) {
                break;
            }
            foreach ($queueMessages as $msg) {
                $userId = $msg->body;
                $userInfo = $userInfo->getByID($userId);
                $userMail = $userInfo->getUserEmail();

                // send mail
                $mailService->reset();
                $mailService->to($userMail);
                $mailService->setBody(t('Hi there'));
                if ($mailService->sendMail()) {
                    $count++;
                } else {
                    $this->output->writeln(t('<error>Mail could not be sent to %s</error>', $userMail));
                }

                // delete message from queue
                $queue->deleteMessage($msg);
            }
        }

        $this->output->writeln(t2('<info>%d Notification sent</info>', '<info>%d Notifications sent</info>', $count, $count));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = Queue::get('queue-demo');
        $queue->send(1);

        $this->input = $input;
        $this->output = $output;
        $this->app = Facade::getFacadeApplication();
        $this->console = $this->getApplication();

        if (!$this->lock()) {
            $this->output->writeln(t('<error>Failed to create lock, please make sure the process is not already running.</error>'));
        } else {
            $this->sendNotifications();
            $this->unlock();
        }
    }

}