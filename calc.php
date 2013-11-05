<?php
include 'Func.php';

/**
 * Zmienne jakie zadeklaruje użytkownik
 * Mogą być nimi m.in. PI
 */
class Variable {
	private $name;
	private $value;

	public function Variable($_name, $_value) {
		$this->name = $_name;
		$this->value = $_value;
	}

	public function setValue($_value) {
		$this->value = $_value;
	}

	public function setName($_name) {
		$this->name = $_name;
	}

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}
}

/**
 * Prościutki wrapper na proceduralne
 * funkjcje php, imo ładniej to wygląda
 * upakowane w klasie
 */
class Stack {
	private $array = array();

	public function size() {
		return count($this->array);
	}

	public function push($_obj) {
		array_push($this->array, $_obj);
	}

	public function getAndPop() {
		if(count($this->array) == 0)
			return;
		$last = $this->getLast();
		array_pop($this->array);
		return $last;
	}

	public function pop() {
		if(count($this->array) == 0)
			return;
		array_pop($this->array);
	}

	public function getArray() {
		return $this->array;
	}

	public function getLast() {
		return $this->array[count($this->array) - 1];
	}

	public function getFirst() {
		return $this->array[0];
	}
}

/**
 * Operator musi mieć swoją nazwę i priorytet,
 * operator jest typu char - ale chujowe typowanie,
 * ktorego niema, w tym php
 */
class Operator {
	private $label;
	private $priority;

	public function Operator($_label, $_priority) {
		$this->label = $_label;
		$this->priority = $_priority;
	}

	public function getLabel() {
		return $this->label;
	}

	public function getPriority() {
		return $this->priority;
	}
}

/**
 * @param unknown $_sentence
 * Obliczanie wyrażenia i zwracanie wyniku,
 * jeśli znajdzie nazwe funkcji to przeskakuje
 * do jej interfejsu
 */
class Calculator {
	private $sentence;
	private $functions;
	private $operators;

	private $operators_stack;	// stos na którym znajdują się operatory
	private $exit;				// stos wyjścia liczby+operatory

	public function Calculator($_sentence, $_functions) {
		$this->sentence = $_sentence;
		$this->functions = clone $_functions;
		$this->operators = array(
				new Operator('+', 2),
				new Operator('-', 2),
				new Operator('*', 3),
				new Operator('/', 3),
				new Operator('^', 4),
				new Operator('(', 0));
		$this->exit = new Stack;
		$this->operators_stack = new Stack;

		if(strlen($this->sentence) == 0) {
			throw new Exception("Sentencja jest pusta!");
		}
		if($this->sentence[strlen($this->sentence) - 1] == '=') {
			$this->sentence = substr($this->sentence, 0, strlen($this->sentence) - 1);
		}
	}

	private function findFunction($_sentence) {
		foreach($this->functions->getArray() as $value) {
			if($value->getLabel() == $_sentence) {
				return $value;
			}
		}
		return NULL;
	}

	private function characterToOperator($_character) {
		foreach($this->operators as $value) {
			if($value->getLabel() == $_character) {
				return $value;
			}
		}
		return NULL;
	}

	public function addOperator($_operator) {
		$this->operators->push($_operator);
	}

	public function getSentence() {
		return $this->sentence;
	}

	private function calcOperator($operator, $right, $left) {
		switch($operator->getLabel()) {
			case '+':
				return $left + $right;
				break;

			case '-':
				return $left - $right;
				break;

			case '*':
				return $left * $right;
				break;

			case '/':
				if($right == 0) {
					throw new Exception("Dzielenie przez 0 jest zabronione!");
				}
				return $left / $right;
				break;

			case '^':
				return pow($left, $right);
				break;
					
			default:
				throw new Exception('Nieznany operator: '.$operator);
		}
	}

	public function getValue() {
		if(strlen($this->sentence) == 0) {
			throw 'Sentencja jest za krotka!';
		}
		return $this->parse();
	}

	/**
	 * Parsowanie i obliczanie wartości wyrażenia ONP!
	 * @return multitype:
	 */
	private function parseToONP() {
		$variable_stack = new Stack;
		for($i = 0; $i < $this->operators_stack->size();$i++) {
			/**
			 * Backportowane do starszej wersji PHP!
			 */
			$_array = $this->operators_stack->getArray();
			$this->exit->push($_array[$i]->getLabel());
		}
		/**
		 * Obliczanie wartości ONP
		 */
		foreach($this->exit->getArray() as $value) {
			$operator = $this->characterToOperator($value);
			if(strlen($value) == 0) {
				continue;
			}
			if($operator != NULL) {
				$variable_stack->push($this->calcOperator($operator, $variable_stack->getAndPop(), $variable_stack->getAndPop()));
			} else {
				$variable_stack->push($value);
			}
		}
		return $variable_stack->getLast();
	}

	/**
	 * @param unknown $_sentence
	 * Obliczanie znalezionej funkcji
	 */
	private function calcFunc($_func, $_sentence) {
		if(strlen($_sentence) > 0) {
			if(strpos($_sentence, ',') !== false) {
				/**
				 * Tokenizer odpada ze względu na to że w funkcji mogą
				 * być funkcje jako argumenty
				 */
				$nested = 0;
				$args = array();
				$buffer = '';

				for($i=0;$i < strlen($_sentence);$i++) {
					if($_sentence[$i] == '(') {
						$nested++;
					}
					if($_sentence[$i] == ')') {
						$nested--;
					}
					if($_sentence[$i] == ',' && $nested == 0) {
						$calc = new Calculator($buffer, $this->functions);
						array_push($args, $calc->getValue());
						$buffer = '';
					} else if($_sentence[$i] != ' ') {
						$buffer = $buffer.$_sentence[$i];
					}
				}
				$calc = new Calculator($buffer, $this->functions);
				array_push($args, $calc->getValue());
				/**
				 * WIELOARGUMENTOWE!!
				*/
				return $_func->calc($args);
			} else {
				$calc = new Calculator($_sentence, $this->functions);
				return $_func->calc(array($calc->getValue()));
			}
		}
	}

	/**
	 * Parsowanie funkcji...
	 */
	private function parseFunctions() {
		for($i = 0; $i < strlen($this->sentence); $i++) {
			foreach($this->functions->getArray() as $func) {
				$pos = strpos($this->sentence, $func->getLabel());
				if($pos !== FALSE && $pos == $i) {
					/**
					 * Jest początek.. teraz obliczyc trza koniec
					 */
					$nested = 0;
					$end = 0;
					for($j = $i;$j < strlen($this->sentence);$j++) {
						if($this->sentence[$j] == '(') {
							$nested++;
						} else if($this->sentence[$j] == ')') {
							$nested--;
							if($nested == 0) {
								$end = $j;
								break;
							}
						}
					}
					/**
					 * Koniec już jest.. tereaz trza przeliczyć funkcje i podmienić..
					 */
					$arg_start_pos = $pos + strlen($func->getLabel()) + 1; // od tej pozycji sa aargumenty 0,1 z sin(0,1)
					$val = $this->calcFunc($func, substr($this->sentence, $arg_start_pos, $end - $arg_start_pos));
					/**
					 * Funkcja policzona.. teraz trza podmienić ją..
					*/
					$this->sentence = substr($this->sentence, 0, $pos).$val.substr($this->sentence, $end + 1);
				}
			}
		}
	}

	/**
	 * Przekształcanie np.: -1+2 na (0-1)+2, 2+-1 na 2+(0-1)
	 */
	private function removeNegative() {
		for($i = 0;$i < strlen($this->sentence);$i++) {
			if($this->sentence[$i] == '-') {
				$pos = -1;
				if($i == 0) {
					$pos = 0;
				} else {
					foreach($this->operators as $value) {
						if($this->sentence[$i - 1] == $value->getLabel()) {
							$pos = $i;
							break 1;
						}
					}
				}
				if($pos != -1) {
					$end = -1;
					for($j = $i + 1;$j < strlen($this->sentence);$j++) {
						foreach($this->operators as $value) {
							if($this->sentence[$j] == $value->getLabel()) {
								$end = $j;
								break 2;
							}
						}
					}
					if($end == -1) {
						$end = strlen($this->sentence);
					}
					$this->sentence = substr($this->sentence, 0, $pos).'(0-'.substr($this->sentence, $pos + 1, $end - ($pos+1)).')'.substr($this->sentence, $end);
				}
			}
		}
	}

	/**
	 * @return multitype:
	 * Parsowanie do ONP
	 */
	private function parse() {
		$buffer = '';

		$this->removeNegative();
		$this->parseFunctions();
		$this->removeNegative();
		/*
		 * tokeny nie wchodzą w gre ze względu na ten wyjątek:
		* -(-1), minusów obok nawiasów nie może być
		*/
		for($i = 0;$i < strlen($this->sentence);$i++) {
			$operator = $this->characterToOperator($this->sentence[$i]);
			if($this->sentence[$i] == '(') {
				$this->exit->push($buffer);
				$buffer = '';

				$this->operators_stack->push($operator);
			} else if($operator != NULL) {
				$this->exit->push($buffer);
				$buffer = '';

				if($this->operators_stack->size() == 0 || $operator->getPriority() > $this->operators_stack->getLast()->getPriority()) {
					$this->operators_stack->push($operator);
				} else if($operator->getPriority() <= $this->operators_stack->getLast()->getPriority()) {
					// Wyrzucanie wszystkich operatorów o priorytecie wyższym bądź równym!
					while($this->operators_stack->size() > 0) {
						$_stack_operator = $this->operators_stack->getLast();
						if($_stack_operator->getPriority() >= $operator->getPriority()) {
							$this->exit->push($_stack_operator->getLabel());
							$this->operators_stack->pop();
						} else {
							break;
						}
					}
					$this->operators_stack->push($operator);
				}
			} else if($this->sentence[$i] == ')') {
				$this->exit->push($buffer);
				$buffer = '';

				while(true) {
					$_stack_operator = $this->operators_stack->getLast();
					$this->operators_stack->pop();
					if($_stack_operator->getLabel() == '(') {
						break;
					} else {
						$this->exit->push($_stack_operator->getLabel());
					}
				}
			} else if($this->sentence[$i] != ' ') {
				$buffer = $buffer.$this->sentence[$i];
			}
		}
		/*
		 * Ostatnia powinna być cyfra..
		*/
		$this->exit->push($buffer);
		return $this->parseToONP();
	}
}

/**
 * Wyświetlanie kalkulatora
 */
$functions = new Stack;
$functions->push(new Sin);
$functions->push(new Cos);
$functions->push(new Tan);
$functions->push(new Atan);

function calcExpression($expression) {
	global $functions;
	try {
		$calc = new Calculator($expression, $functions);
		return $calc->getValue();
	} catch(Exception $exception) {
		/**
		 * Łoo, trza obsłużyć wyjąteczek!
		 */
		print '</ br><b><p style="color:red"> File:'.$exception->getFile().'   Line:  '.$exception->getLine().'   '.$exception->getMessage().'</p> </ br></b>';
	}
	return 0;
}

/**
 * Rysowanie dostępnych funkcji..
 */
function printAvailableFunctions() {
	global $functions;
	$out = '<p style="color:blue; font-weight:bold">Dostępne funkcje:</p><ul>';
	foreach($functions->getArray() as $value) {
		$out = $out.'<li>'.$value->getLabel().'(';
		for($i = 0; $i < $value->getArgCount();$i++) {
			$out = $out.'arg'.$i;
			if($i + 1 < $value->getArgCount()) {
				$out = $out.',';
			}
		}
		$out = $out.')</li>';
	}
	return $out.'</ul>';
}

/**
 * Rysowanie całej strony!
 */
function printPage($expression, $val) {
	echo '
			<!DOCTYPE html>
			<html lang="pl">
			<head>
			<meta charset="UTF-8" />
			<title>Kalkulator!</title>
			</head>
			<body>
			'.printAvailableFunctions().'
					<form action="calc.php" method="GET">
					<input type="text" size="41" name="expression" value='.$expression.'></input>
							<button type=submit>=</button>
							<input type="text" size="41" name="value" value='.$val.'></input>
									</form>
									</body>
									</html>';
}

$expression = $_GET['expression'];
printPage($expression, calcExpression($expression));
?>
