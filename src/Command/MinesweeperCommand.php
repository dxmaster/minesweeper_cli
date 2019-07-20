<?php

namespace Minesweeper\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MinesweeperCommand extends Command
{
    /**
     * @var SymfonyStyle $io
     */
    private $io;

    /**
     * @var OutputInterface $consoleOutput
     */
    private $consoleOutput;

    /**
     * @var InputInterface $consoleInput
     */
    private $consoleInput;

    /**
     * Game field, 2-dimension array
     *
     * @var array $field
     */
    private $field;

    /**
     * List of already opened cells
     *
     * @var array $openedCells
     */
    private $openedCells;

    /**
     * @var int $rows
     */
    private $rows;

    /**
     * @var int $cols
     */
    private $cols;

    /**
     * @var int $mines
     */
    private $mines;

    /**
     * @var bool $gameOver
     */
    private $gameOver;

    protected function configure()
    {
        $this->setName('minesweeper:play')
            ->setDescription('Play the game')
            ->setDefinition(new InputDefinition([
                new InputOption('cols', 'c', InputOption::VALUE_OPTIONAL, 'Number of cols of field', 30),
                new InputOption('rows', 'r', InputOption::VALUE_OPTIONAL, 'Number of rows of field', 20),
                new InputOption('mines', 'm', InputOption::VALUE_OPTIONAL, 'Number of mines < (cols x rows)', 25),
            ]));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*
         * Store variables for accessing from class
         */
        $this->consoleInput = $input;
        $this->consoleOutput = $output;
        $this->io = new SymfonyStyle($this->consoleInput, $this->consoleOutput);

        try {
            /*
             * Parsing inputted startup variables
             */
            $this->cols = filter_var($input->getOption('cols'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($this->cols === false) {
                throw new Exception("Wrong number of columns");
            }

            $this->rows = filter_var($input->getOption('rows'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($this->rows === false) {
                throw new Exception("Wrong number of rows");
            }

            $maxMines = ($this->cols * $this->rows) - 1;
            $this->mines = filter_var($input->getOption('mines'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maxMines]]);
            if ($this->mines === false) {
                throw new Exception(sprintf("Wrong number of mines %d, it should be in range from 1 to %d", $input->getOption('mines'), $maxMines));
            }

            /*
             * Start the Game
             */
            $this->io->title('=== Minesweeper Game ===');

            $this->initializeGame();
            $this->renderField();

            $this->io->newLine(2);
            $this->io->text('If you want close app, then type "quit" or press "Ctrl+C"');

            $this->mainGameLoop();

        } catch (Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    /**
     * Initialize game variables and create game field
     */
    function initializeGame()
    {
        /*
         * Initialize game variables
         */
        $this->gameOver = false;
        $this->field = [];
        $this->openedCells = [];

        /*
         * The game field is array [rows, cols] of integers, where each cell could be in range -1 ... 8
         * The value -1 means that is Mine cell
         * The number 0 .. 8 is count of mines around this cell
         */

        /*
         * Сreate a game field and fill cells by 0 value
         * In same time we create $blankCells list where we store list of all cells on the field. Each key of list is row:col
         */
        $blankCells = [];
        for ($r = 0; $r < $this->rows; $r++) {
            $this->field[$r] = [];
            for ($c = 0; $c < $this->cols; $c++) {
                $this->field[$r][$c] = 0;
                $blankCells[$r . ":" . $c] = ['row' => $r, 'col' => $c];
            }
        }

        /*
         * Generating and storing mines positions.
         * Getting random keys from $blankCells list
         * These keys will be the position of mines
         */
        $minesPositions = [];
        $randomPositions = array_rand($blankCells, $this->mines);
        if ($this->mines == 1) {
            $minesPositions[$randomPositions] = $blankCells[$randomPositions];
        } else {
            foreach ($randomPositions as $randomPosition) {
                $minesPositions[$randomPosition] = $blankCells[$randomPosition];
            }
        }

        /*
         * Mark cells with mines on the field (Set -1)
         */
        foreach ($minesPositions as $k => $v) {
            $this->field[$v['row']][$v['col']] = -1;
        }

        /*
         * Updating counters in cells if they have mines near.
         * Go throught all mines positions and iterate all nearest cells         *
         */
        foreach ($minesPositions as $k => $v) {
            for ($r = max(0, $v['row'] - 1); $r <= min($v['row'] + 1, $this->rows - 1); $r++) {
                for ($c = max(0, $v['col'] - 1); $c <= min($v['col'] + 1, $this->cols - 1); $c++) {
                    if ($this->field[$r][$c] != -1) {
                        $this->field[$r][$c]++;
                    }
                }
            }
        }
    }

    /**
     * Main game loop
     */
    function mainGameLoop()
    {
        /*
         * Main game loop:
         * 1. Wait for user input
         * 2. Validate input
         * 3. Change game state
         * 4. Display field
         */
        while (!$this->gameOver) {
            /*
             * User input
             * Using loop until user does not enter a valid value OR press Ctrl+C OR quit
             */
            do {
                /**
                 * Result of user`s input
                 *
                 * @var array|string|bool $answer
                 */
                $answer = $this->io->ask("Make a turn, please. Input Row and Column. For example: 2,4", null, function ($inp_value) {
                    if (strtolower($inp_value) == "quit") {
                        return "quit";
                    }

                    list($row, $col) = sscanf($inp_value, "%d,%d");

                    if ((int)$row == 0 || (int)$row > $this->rows || (int)$col == 0 || (int)$col > $this->cols) {
                        $this->io->warning("Wrong input, try again");
                        return false;
                    }

                    if (isset($this->openedCells[($row - 1) . ":" . ($col - 1)])) {
                        $this->io->note("This cell is already opened");
                        return false;
                    }

                    /*
                     * Correct inputed Row and Col by 1
                     */
                    return ['row' => $row - 1, 'col' => $col - 1];
                });
            } while ($answer === false);

            /*
             * Close application if typed "quit"
             */
            if ($answer === "quit") {
                $this->io->text('Buy!');
                break;
            }

            /*
             * Check game state
             * If mine was clicked - then game over
             * If not mine, then add the cell to list with already opened cells
             */
            if ($this->field[$answer['row']][$answer['col']] == -1) {
                $this->gameOver = true;
            } else {
                // value of array does not matter, key is used
                $this->openedCells[$answer['row'] . ":" . $answer['col']] = 0;
            }

            /*
             * Check for non opened cells. If all cells are opened then game over
             */
            if (count($this->openedCells) + $this->mines == ($this->rows * $this->cols)) {
                $this->gameOver = true;
            }

            $this->renderField();
        }

        if ($this->gameOver) {
            /*
             * If user opened all non-mine cells then he won
             */
            if (count($this->openedCells) + $this->mines == ($this->rows * $this->cols)) {
                $this->io->text('You Win!');
            } else {
                $this->io->text('Game Over!');
            }
        }
    }

    /**
     * Display field
     */
    function renderField()
    {
        /*
         * Calculate number of characters in column and headers
         */
        $padColumnLenght = strlen((string)$this->cols) + 2;
        $padRowHeaderLenght = strlen((string)$this->rows) + 1;

        /*
         * Print Columns header
         */
        $topHeader = "";

        $topHeader .= str_repeat(" ", $padRowHeaderLenght);
        $numbersLine = "";
        for ($c = 0; $c < $this->cols; $c++) {
            $numbersLine .= str_pad($c + 1, $padColumnLenght, " ", STR_PAD_LEFT);
        }
        $topHeader .= $numbersLine . PHP_EOL;
        $topHeader .= str_repeat(" ", $padRowHeaderLenght);
        $topHeader .= str_repeat("_", strlen($numbersLine));

        $this->consoleOutput->writeln($topHeader);


        /*
         * Print main fileld area
         * If not game over, then prints normal field
         * If game over, then expose all cells
         */
        if (!$this->gameOver) {
            for ($r = 0; $r < $this->rows; $r++) {
                // Print Row header column
                $this->consoleOutput->write(str_pad(($r + 1) . "|", $padRowHeaderLenght, " ", STR_PAD_LEFT));

                for ($c = 0; $c < $this->cols; $c++) {
                    if (isset($this->openedCells[$r . ":" . $c])) {
                        $this->consoleOutput->write(str_pad($this->field[$r][$c], $padColumnLenght, " ", STR_PAD_LEFT));
                    } else {
                        $this->consoleOutput->write(str_pad("_", $padColumnLenght, " ", STR_PAD_LEFT));
                    }
                }
                $this->consoleOutput->writeln("");
            }
        } else {
            for ($r = 0; $r < $this->rows; $r++) {
                // Print Row header column
                $this->consoleOutput->write(str_pad(($r + 1) . "|", $padRowHeaderLenght, " ", STR_PAD_LEFT));

                for ($c = 0; $c < $this->cols; $c++) {
                    if ($this->field[$r][$c] != -1) {
                        $this->consoleOutput->write(str_pad($this->field[$r][$c], $padColumnLenght, " ", STR_PAD_LEFT));
                    } else {
                        $this->consoleOutput->write(str_pad("X", $padColumnLenght, " ", STR_PAD_LEFT));
                    }
                }
                $this->consoleOutput->writeln("");
            }
        }
    }

    /**
     * Get game field
     *
     * @return array
     */
    public function getField(): array
    {
        return $this->field;
    }

    /**
     * Get already opened cells
     *
     * @return array
     */
    public function getOpenedCells(): array
    {
        return $this->openedCells;
    }

    /**
     * Get number of rows
     *
     * @return int
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    /**
     * Get number of cols
     *
     * @return int
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Get number of mines
     * 
     * @return int
     */
    public function getMines(): int
    {
        return $this->mines;
    }

    /**
     * Get game status
     * 
     * @return bool
     */
    public function isGameOver(): bool
    {
        return $this->gameOver;
    }
}
