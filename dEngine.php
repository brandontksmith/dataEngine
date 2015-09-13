<?php
/**
 * dEngine Class Parses and Manipulates Files
 *
 * Simply Creates and Deletes Files, Updates, Removes, or
 * Appends Data to Files, and Selects Data from Files using
 * (Optional) Conditionals and Parameters.
 *
 * @package dEngine
 * @version 0.1
 * @author Brandon Smith <brandontksmith@gmail.com>
 */

class dEngine
{
	/**
	 * File Pointer Resource
	 *
	 * @var resource
	 * @since 0.1
	 */
	private $handle = null;
	
	/**
	 * Path to Directory
	 *
	 * @var string
	 * @since 0.1
	 */
	private $path = __DIR__;
	
	/**
	 * Last Error Message
	 *
	 * @var string
	 * @since 0.1
	 */
	private $error = null;
	
	/**
	 * dEngine Class Constructor Method
	 *
	 * @param string $path Directory path
	 */ 
	public function __construct($path)
	{
		if (is_dir($path))
		{
			$this->path = realpath($path) . '/';
		}
		else
		{
			$this->setError($path . ' is not an existent directory.');
		}
	}
	
	/**
	 * Getter Function for $handle Variable
	 *
	 * @return resource|null
	 */
	public function getHandle()
	{
		return $this->handle;
	}
	
	/**
	 * Getter Function for $path Variable
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
	
	/**
	 * Getter Function for $error Variable
	 *
	 * @return string|null
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Setter Function for $handle Variable
	 *
	 * @param resource $handle File Pointer
	 */
	public function setHandle(&$handle)
	{
		$this->handle = $handle;
	}
	
	/**
	 * Setter Function for $path Variable
	 *
	 * @param string $path Directory
	 */
	public function setPath($path) 
	{
		$this->path = $path;
	}
	
	/**
	 * Setter Function for $error Variable
	 *
	 * @param string $error Error Message
	 */
	public function setError($error)
	{
		$this->error = $error;
	}
	
	/**
	 * Opens a File in the Specified Mode
	 *
	 * @param string $file File Name
	 * @param string $mode Access Mode
	 * @param boolean $create Create File
	 * @return boolean|resource
	 */
	public function open($file, $mode, $create = false)
	{
		$path = $this->getPath();
		
		if (!file_exists($path . $file) && !$create)
		{
			$this->setError($file . ' does not exist in ' . $path);
			return false;
		}
		
		$handle = fopen($path . $file, $mode);
		
		if (!$handle)
		{
			$this->setError('Unable to open a stream to the file.');
			return false;
		}
		
		$this->setHandle($handle);
		return $handle;	
	}
	
	/**
	 * Attempts to Close File Pointer Resource
	 *
	 * @return boolean
	 */
	public function close()
	{
		$handle = $this->getHandle();
		
		if ($this->getHandle() === null)
		{
			return false;
		}
		
		if (!fclose($handle))
		{
			$this->setError('Failed to close file pointer');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns/Gets Data from a File
	 *
	 * @param string $file File Name
	 * @param boolean $format Format Data
	 * @param string|int $lines Lines
	 * @return boolean|array
	 */
	public function getData($file, $format, $lines = '*')
	{
		$handle = $this->open($file, 'r');
		
		// The Number of Lines Read
		$linesRead = 0;
		
		if (!$handle)
		{
			return false;
		}
		
		while (!feof($handle))
		{
			// If $lines is not equal to '*' and $linesRead is > than $lines
			if ($lines !== '*' && $linesRead >= $lines)
			{
				break;
			}
			
			// Strip White Spaces from Beginning and End
			$line = trim(fgets($handle));
			
			// Ignore Empty Lines
			if (empty($line) || $line === null)
			{
				continue;
			}
			
			// Return Data with Formated Keys (Field Names)
			if ($format)
			{
				$e = explode("\t", $line);
				
				if ($e[0] === 'fields')
				{
					// Parse the Fields and append to $arr
					$fields = explode(',', $e[1]);
					$arr = array($e[0] => $fields);
				}
				else
				{
					// Get the Field Names from the Dara Array
					$lookup = array_values($data[0]['fields']);
					
					for ($i = 0; $i < count($lookup); $i++)
					{
						$arr[$lookup[$i]] = $e[$i];
					}
				}
			}
			
			$data[] = isset($arr) ? $arr : $line;
			unset($arr); // Unset Variable
			$linesRead++; // Increment linesRead
		}
		
		return $data;
	}
	
	/**
	 * Writes Data to a File in the Specified Mode
	 *
	 * @param string $file File Name
	 * @param array $data Data to Write
	 * @param boolean $string Access Mode
	 * @return boolean
	 */
	public function writeData($file, $data, $mode)
	{
		$handle = $this->open($file, $mode);
		
		if (!$handle)
		{
			return false;
		}
		
		$flag = fwrite($handle, $data . "\n");
		fclose($handle);
		
		if (!$flag)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns an Array to be Writed to File
	 *
	 * @param array $arr Array to Parse
	 * @return array
	 */
	public function toWritableFormat($arr)
	{
		for ($i = 0; $i < count($arr); $i++)
		{
			if ($i === 0)
			{
				// First Line of File; contains fields formatted by a comma
				$arr[$i] = 'fields' . "\t" . implode(",", $arr[$i]['fields']);
			}
			else
			{
				// Additional Lines in File contains values separated by tabs
				$arr[$i] = implode("\t", $arr[$i]);
			}
		}
		
		return $arr = implode("\n", $arr);
	}
	
	/**
	 * Formats Conditional Arrays
	 *
	 * @return boolean|array
	 */
	public function formatC()
	{
		// First Arguement will be Conditional
		// Second Arguement will be Field Name
		// Additional Arguements will be Values
		$args = func_get_args();
		
		if (count($args) < 3)
		{
			$this->setError('dEngine::formatC() expects at least 3 arguments.');
			return false;
		}
		
		for ($i = 2; $i < count($args); $i++)
		{
			$values[] = $args[$i];
		}
		
		$arr = array($args[0], $args[1], $values);
		
		return $arr;
	}
	
	/**
	 * Converts Arguements to an Array
	 *
	 * @return array
	 */
	public function toArray()
	{
		$args = func_get_args();
		
		for ($i = 0; $i < count($args); $i++)
		{
			$arr[] = $args[$i];
		}
		
		return $arr;
	}
	
	
	/**
	 * Verifies the Existence of Fields
	 *
	 * @param array $arr Data Array
	 * @param array $fields Check Fields
	 * @return boolean
	 */
	public function fieldsExists($arr, $fields)
	{
		if (!is_array($fields) || !is_array($arr))
		{
			$this->setError('$dEngine::fieldsExists() expects two array arguments.');
			return false;
		}
		
		foreach ($fields as $field)
		{
			if ($field === '*')
			{
				return true;
			}
			
			if (!in_array($field, $arr[0]['fields']))
			{
				$this->setError('You have specified Invalid Fields.');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Checks Conditionals on a Piece of Data
	 *
	 * @param array $arr Data Array
	 * @param array $c Conditional Array
	 * @return int $flag Number of Checks
	 */
	public function checkC($arr, $c)
	{
		// Number of True Conditional Checks
		$flag = 0;
		
		for ($j = 0; $j < count($c); $j++)
		{
			// Checks 'WHERE' Conditionals
			if ($c[$j][0] === 'WHERE')
			{
				$field = $c[$j][1];
				$values = $c[$j][2];
				
				foreach ($values as $value)
				{
					if ($arr[$field] === $value)
					{
						$flag++;
						break;
					}
				}
			}
		}
		
		return $flag;
	}
	
	/**
	 * Selects and Returns Data from a File
	 *
	 * @param string $file File Name
	 * @param array $fields Selected Fields
	 * @param array $c Conditionals
	 * @return boolean|array
	 */
	public function select($file, $fields, $c = array())
	{
		// Get Data formatted with Field Names
		$arr = $this->getData($file, true);
		
		// Result of select() method
		$result = array();
		
		if (!$arr)
		{
			return false;
		}
		
		for ($i = 0; $i < count($arr); $i++)
		{
			// First Line should contain Fields
			if ($i === 0)
			{
				// Checks Existence of Fields in $arr
				if (!$this->fieldsExists($arr, $fields))
				{
					return false;
				}
				
				continue;
			}
			
			// Checks if $arr[$i] passes the Conditional Checks 
			$flag = $this->checkC($arr[$i], $c);
			
			// $flag will equal the number of $c elements if all conditions are met
			if ($flag === count($c))
			{
				$index = count($result);
				
				foreach ($fields as $field)
				{
					// All Field Selects; add to $result
					if ($field === '*')
					{
						$result[] = $arr[$i];
						break;
					}
					
					// Add Specified Fields to $result
					$result[$index][$field] = $arr[$i][$field];
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Updates a Field in Line(s) to a New Value
	 *
	 * @param string $file File Name
	 * @param string $field Field Name
	 * @param string $newValue Changed Value
	 * @param array $c Conditionals
	 * @return boolean
	 */
	public function update($file, $field, $newValue, $c = array())
	{
		// Get Data formatted with Field Names
		$arr = $this->getData($file, true);
		
		if (!$arr)
		{
			return false;
		}
		
		for ($i = 0; $i < count($arr); $i++)
		{
			// First Line should contain Fields
			if ($i === 0)
			{
				// Checks Existence of Fields in $arr
				if (!$this->fieldsExists($arr, (array) $field))
				{
					return false;
				}
				
				continue;
			}
			
			// Checks if $arr[$i] passes the Conditional Checks 
			$flag = $this->checkC($arr[$i], $c);
			
			// $flag will equal the number of $c elements if all conditions are met
			if ($flag === count($c))
			{
				$arr[$i][$field] = $newValue;
			}
		}
		
		// Convert the Array to a Writable Format
		$arr = $this->toWritableFormat($arr);
		
		// Write Updated data to the File
		if (!$this->writeData($file, $data, 'w+'))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Appends a New Line at the end of a File
	 *
	 * @param string $file File Name
	 * @param array $fields Fields
	 * @param array $values Values
	 * @return boolean
	 */
	public function append($file, $fields, $values)
	{
		$arr = $this->getData($file, true, 1);
		
		if (!$arr || !$arr[0]['fields'])
		{
			return false;
		}
		
		if (!$this->fieldsExists($arr, $fields))
		{
			return false;
		}
		
		$values = implode("\t", $values);
		
		if (!$this->writeData($file, $values, 'a'))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Removes Line(s) Based on Conditional
	 *
	 * @param string $file File Name
	 * @param array $c Conditionals
	 * @return boolean
	 */
	public function remove($file, $c)
	{
		// Get Data formatted with Field Names
		$arr = $this->getData($file, true);
		
		// Contains the data to be written to $file 
		$result = array();
		
		if (!$arr)
		{
			return false;
		}
		
		for ($i = 0; $i < count($arr); $i++)
		{
			// Check all Lines except the first
			if ($i !== 0)
			{
				$flag = $this->checkC($arr[$i], $c);
			}
			
			// Add lines that do not meet all conditional checks
			if ($flag !== count($c))
			{
				$result[] = $arr[$i];
			}
		}
		
		// Convert the Result to a Writable Format
		$result = $this->toWritableFormat($result);
				
		if (!$this->writeData($file, $result, 'w'))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Creates a File in Class Directory with Fields
	 *
	 * @param string $file File Name
	 * @param array $fields Fields
	 * @return boolean
	 */
	public function create($file, $fields)
	{
		$path = $this->getPath();
		
		if (file_exists($path . $file))
		{
			$this->setError('Unable to create an already existing file: ' . $file . ' in ' . $path);
			return false;
		}
		
		$data = 'fields' . "\t" . implode(",", $fields);
		
		$handle = $this->open($file, 'w', true);
		
		if (!$handle)
		{
			return false;
		}
		
		$flag = fwrite($handle, $data . "\n");
		fclose($handle);
		
		if (!$flag)
		{
			$this->setError('Failed to create file: ' . $file . ' in ' . $path);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Deletes a File in the Directory
	 *
	 * @param string $file File Name
	 * @return boolean
	 */
	public function delete($file)
	{
		$path = $this->getPath();
		
		if (!file_exists($path . $file))
		{
			$this->setError('Unable to delete non-existent file: ' . $file . ' in ' . $path);
			return false;
		}
		
		if (!unlink($path . $file))
		{
			$this->setError('Failed to delete file: ' . $file . ' in ' . $path);
			return false;
		}
		
		return true;
	}
}

?>
