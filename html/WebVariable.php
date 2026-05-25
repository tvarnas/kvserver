<?php

class WebVariable
{
	protected $name='';
	protected $valueArray;

	/////////////////////////////////////////////////////////////////////////////////
	// CONSTRUCT THE OBJECT AND IMMEDIATELY TRY TO load() IT
	public function __construct($name)
	{
		$this->name = $name;
		$this->valueArray = array();
		$this->load();
	}

	/////////////////////////////////////////////////////////////////////////////////
	// RETRIEVES THE NAME OF THE NAME/VALUE PAIR.  THIS IS METHID NOT REALLY NEEDED.
	// THE NAME IS NORMALLY PASSED IN THE CONSTRUCTOR SO THE CALLING PROGRAM ALREADY
	// KNOWS WHAT THE NAME IS.
	public function getName()
	{
		return $this->name;
	}

	/////////////////////////////////////////////////////////////////////////////////
	// SETS THE NAME OF THE NAME/VALUE PAIR USED IN THE WebVariable.  THIS METHOD IS
	// NORMALLY NOT CALLED BUTNAME PASSED IN THE CONSTRUCTOR.
	public function setName($x)
	{
		$this->name = $x;
	}

	/////////////////////////////////////////////////////////////////////////////////
	// THIS METHOD GETS THE WebVariable VALUE IF SET.  NOTE: IT ONLY RETURNS THE
	// FIRST VALUE OF THE DATA ARRAY (THE MOST COMMON USE).  TO GET ALL ARRAY VALUES
	// YOU WILL NEED TO CALL getValueArray() AND ITERATE MANUALLY.
	public function getValue()
	{
		if (isset($this->valueArray[0]))
		{
			return $this->valueArray[0];
		}
		else
		{
			return "";
		}
	}

	/////////////////////////////////////////////////////////////////////////////////
	// THIS SETS A VALUE, NOTE: IT ACTUALLY SETS THE FIRST VALUE OF AN ARRAY
	// THIS METHOD IS USUALLY NOT CALLED; THE load() METHOD FILLS IN VALUES.
	public function setValue($x)
	{
		$this->valueArray[0] = $x;
	}

	/////////////////////////////////////////////////////////////////////////////////
	// RETURNS THE RAW DATA ARRAY FOR FURTHER INSPECTION OUTSIDE THE OBJECT.
	// CHECKBOXES AND BUTTONS ARE KNOWN TO RETURN ARRAYS, NOT SINGLE VALUES.
	public function getValueArray()
	{
		return $this->valueArray;
	}

	/////////////////////////////////////////////////////////////////////////////////
	// RETURNS A TRUE OR FALSE WHETHER THE VARIABLE WAS FOUND, *NOT* IF VARIABLE IS
	// AN EMPTY STRING OR NOT
	public function isValueSet()
	{
		if (count($this->valueArray) == 0) { return FALSE; }
		else
		{
			return TRUE;
		}
	}

	/////////////////////////////////////////////////////////////////////////////////
	// THIS FUNCTION TRIES TO SET THE VALUE WITH A POST, IF NOT THEN TRIES A GET
	public function load()
	{
		$retCode = FALSE;

		if (isset($_POST[ $this->name ]))
		{
			$value = $_POST[$this->name ];
			if (is_array($value))
			{
				$nValue = count($value);
				for ($i=0; $i < $nValue; $i++)
				{
					array_push($this->valueArray, $value[$i]);
				}
			}
			else
			{
				$this->valueArray[0] = $value;
			}

			$retCode = TRUE;
		}
		elseif (isset($_GET[ $this->name ]))
		{
			$value=urldecode( $_GET[$this->name ] );
			if (is_array($value))
			{
				$nValue = count($value);
				for ($i=0; $i < $nValue; $i++)
				{
					array_push($this->valueArray, $value[$i]);
				}
			}
			else
			{
				$this->valueArray[0] = $value;
			}

			$retCode = TRUE;
		}

		return $retCode;
	}

	/////////////////////////////////////////////////////////////////////////////////
	// CONVENIENCE METHOD THAT CREATES A FORM HIDDEN VARIABLE FROM ITS NAME/VALUE
	// PAIR.  NOTE: ONLY FIRST ELEMENT IN DATA ARRAY IS SUPPORTED.
	public function persistAsHiddenVariable()
	{
		$output = "";

		$output = $output . '<input type="hidden" ';
		$output = $output . 'id="' . urlencode($this->name) . '" ';
		$output = $output . 'name="' . urlencode($this->name) . '" ';
		$output = $output . 'value="' . urlencode($this->valueArray[0]) . '" ';
		$output = $output . "/>\n";

		return $output;
	}
}

?>
