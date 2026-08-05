<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration;

use Matomo\Log\NullLogger;
use Matomo\Option;
use Matomo\Scheduler\RetryableException;
use Matomo\Scheduler\ScheduledTaskLock;
use Matomo\Scheduler\Scheduler;
use Matomo\Scheduler\Task;
use Matomo\Scheduler\Timetable;
use Matomo\Tests\Framework\Mock\Concurrency\LockBackend\InMemoryLockBackend;
use Matomo\Tests\Framework\Mock\PiwikOption;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Scheduler
 * @group SchedulerRetryTaskTest
 */
class RetryScheduledTaskTest extends IntegrationTestCase
{
    public function testRetryCount()
    {
        $timetable = new Timetable();

        $task1 = 'task1';
        $task2 = 'task2';

        // Should be zero if no retry entry present
        $this->assertEquals(0, $timetable->getRetryCount($task1));

        // Should increment by one
        $timetable->incrementRetryCount($task1);
        $this->assertEquals(1, $timetable->getRetryCount($task1));

        // Should not break if more than one tasks is counting retries
        $timetable->incrementRetryCount($task2);
        $timetable->incrementRetryCount($task2);
        $this->assertEquals(2, $timetable->getRetryCount($task2));
        $timetable->incrementRetryCount($task1);
        $this->assertEquals(2, $timetable->getRetryCount($task1));

        // Should clear retry count without affecting other tasks
        $timetable->clearRetryCount($task1);
        $this->assertEquals(0, $timetable->getRetryCount($task1));
        $this->assertEquals(2, $timetable->getRetryCount($task2));
        $timetable->clearRetryCount($task2);
        $this->assertEquals(0, $timetable->getRetryCount($task1));
    }

    public function testTaskIsRetriedIfRetryableExcetionIsThrown()
    {

        // Mock timetable
        $now = time() - 60;
        $taskName = 'Matomo\Tests\Integration\RetryScheduledTaskTest.exceptionalTask';
        $timetableData = serialize([$taskName => $now]);

        self::stubPiwikOption($timetableData);

        // Create task
        $dailySchedule = $this->createPartialMock('Matomo\Scheduler\Schedule\Daily', array('getTime'));
        $dailySchedule->expects($this->any())
            ->method('getTime')
            ->will($this->returnValue($now));

        // Setup scheduler
        $tasks = [new Task($this, 'exceptionalTask', null, $dailySchedule)];
        $taskLoader = $this->createMock('Matomo\Scheduler\TaskLoader');
        $taskLoader->expects($this->atLeastOnce())
            ->method('loadTasks')
            ->willReturn($tasks);

        $scheduler = new Scheduler($taskLoader, new NullLogger(), new ScheduledTaskLock(new InMemoryLockBackend()));

        // First run
        $scheduler->run();
        $nextRun = $scheduler->getScheduledTimeForMethod('Matomo\Tests\Integration\RetryScheduledTaskTest', 'exceptionalTask', null);

        // Should be rescheduled one hour from now
        $this->assertEquals($now + 3660, $nextRun);

        self::resetPiwikOption();
    }

    public function testTaskIsNotRetriedIfNormalExcetionIsThrown()
    {
        // Mock timetable
        $now = time() - 60;
        $taskName = 'Matomo\Tests\Integration\RetryScheduledTaskTest.normalExceptionTask';
        $timetableData = serialize([$taskName => $now]);
        self::stubPiwikOption($timetableData);

        // Create task
        $specificSchedule = $this->createPartialMock('Matomo\Scheduler\Schedule\SpecificTime', array('getTime'));
        $specificSchedule->setScheduledTime($now + 50000);
        $specificSchedule->expects($this->any())
            ->method('getTime')
            ->will($this->returnValue($now));

        // Setup scheduler
        $tasks = [new Task($this, 'normalExceptionTask', null, $specificSchedule)];
        $taskLoader = $this->createMock('Matomo\Scheduler\TaskLoader');
        $taskLoader->expects($this->atLeastOnce())
            ->method('loadTasks')
            ->willReturn($tasks);

        $scheduler = new Scheduler($taskLoader, new NullLogger(), new ScheduledTaskLock(new InMemoryLockBackend()));

        // First run
        $scheduler->run();
        $nextRun = $scheduler->getScheduledTimeForMethod('Matomo\Tests\Integration\RetryScheduledTaskTest', 'normalExceptionTask', null);

        // Should not have scheduled for retry
        $this->assertEquals($now + 50000, $nextRun);

        self::resetPiwikOption();
    }

    public function exceptionalTask()
    {
        throw new RetryableException('This task fails and should be retried');
    }

    public function normalExceptionTask()
    {
        throw new \Exception('This task fails and should not be retried');
    }

    private static function stubPiwikOption($timetable)
    {
        Option::setSingletonInstance(new PiwikOption($timetable));
    }

    private static function resetPiwikOption()
    {
        Option::setSingletonInstance(null);
    }
}
