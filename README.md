Minesweeper
=====================
This console application is based on Symfony Console component

### Installing
```
cd some_dir/
git clone https://github.com/dxmaster/minesweeper_cli
composer install
```

### Running
```
cd some_dir/
php minesweeper.php
```

The game will start with 20x30 game field and 25 mines
You can set other setting using command line parameters:

##Parameters:
**Number of rows of the game field:**

--rows=10 or -r 10

**Number or columns  of the game field:**

--cols=5 or -c 5

**Number of mines on the field**

--mines=6 or -m 6

### Example
```
php minesweeper.php --rows=10 --cols=15 --mines=20
```