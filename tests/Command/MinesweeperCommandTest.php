<?php

namespace Command;

use Minesweeper\Command\MinesweeperCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

class MinesweeperCommandTest extends TestCase
{
    /**
     * State of random generator for tests
     *
     * @var int $seekRandomValue
     */
    private $seekRandomValue = 0;

    /**
     * Etalon array for field
     *
     * @var array $etalonGameField
     */
    private $etalonGameField;


    protected function setUp(): void
    {
        srand($this->seekRandomValue);

        $this->etalonGameField =
            [
                [-1, 1, 0],
                [1, 2, 1],
                [0, 1, -1]
            ];
    }


    /**
     * Test game starting with default parameters
     */
    public function testDefaultExecute()
    {
        $command = new MinesweeperCommand();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('=== Minesweeper Game ===', $output);
    }

    /**
     * Test game starting with different parameters
     */
    public function testExecuteWithDifferentvariables()
    {
        $command = new MinesweeperCommand();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        /*
         * Wrong Row number: "0"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => 0
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of rows', $output, "Wrong Row number: \"0\"");

        /*
         * Wrong Row number "-1"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => null
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of rows', $output, "Wrong Row number \"-1\"");


        /*
         * Wrong Row number "empty"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => null
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of rows', $output, "Wrong Row number \"empty\"");

        /*
         * Wrong Col number: "0"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--cols' => 0
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of columns', $output, "Wrong Col number: \"0\"");

        /*
         * Wrong Col number "-1"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--cols' => -1
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of columns', $output, "Wrong Col number \"-1\"");

        /*
         * Wrong Col number "empty"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--cols' => null
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of columns', $output, "Wrong Col number \"empty\"");

        /*
         * Wrong Mines number "0"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--mines' => 0
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of mines 0', $output, "Wrong Mines number \"0\"");

        /*
         * Wrong Mines number "-1"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--mines' => -1
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of mines -1', $output, "Wrong Mines number \"-1\"");

        /*
         * Wrong Mines number "empty"
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--mines' => null
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of mines 0', $output, "Wrong Mines number \"empty\"");

        /*
         * Wrong Mines number Rows * Cols > Mines
         */
        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => 3,
            '--cols' => 3,
            '--mines' => 9,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Wrong number of mines 9', $output, "Wrong Mines number Rows * Cols > Mines");
    }

    /**
     * Test game flow for winning
     */
    public function testWinGame()
    {
        $command = new MinesweeperCommand();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['1,2', '1,3', '2,1', '2,2', '2,3', '3,1', '3,2']);

        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => 3,
            '--cols' => 3,
            '--mines' => 2,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('You Win', $output, "Win check");
    }

    /**
     * Test game flow for loosing
     */
    public function testLoseGame()
    {
        $command = new MinesweeperCommand();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['1,1']);

        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => 3,
            '--cols' => 3,
            '--mines' => 2,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Game Over', $output, "Game Over check");
    }

    /**
     * Test the initialize game process
     */
    public function testInitializeGame()
    {
        $command = new MinesweeperCommand();

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            '--rows' => 3,
            '--cols' => 3,
            '--mines' => 2,

        ]);

        $this->assertEquals($this->etalonGameField, $command->getField(), "Wrong field was generated");
        $this->assertEquals(false, $command->isGameOver(), "Bad game status");
        $this->assertEquals([], $command->getOpenedCells(), "Bad opened cells array");
    }
}
