<?php
namespace NumericDataTypes\Form\Element;

use Zend\Form\Element;

class DateTime extends Element
{
    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     */
    const YEAR_MIN = -292277022656;
    const YEAR_MAX =  292277026595;

    protected $valueElement;
    protected $yearElement;
    protected $monthElement;
    protected $dayElement;
    protected $hourElement;
    protected $minuteElement;
    protected $secondElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->valueElement = new Element\Hidden($name);
        $this->valueElement->setAttributes([
            'class' => 'numeric-datetime-value',
        ]);

        $this->yearElement = new Element\Number('year');
        $this->yearElement->setAttributes([
            'class' => 'numeric-datetime-year',
            'step' => 1,
            'min' => self::YEAR_MIN,
            'max' => self::YEAR_MAX,
            'placeholder' => 'Year', // @translate
        ]);

        $this->monthElement = new Element\Select('month');
        $this->monthElement->setAttributes([
            'class' => 'numeric-datetime-month',
        ]);
        $this->monthElement->setEmptyOption('Month'); // @translate
        $this->monthElement->setValueOptions([
            1 => 'January', // @translate
            2 => 'February', // @translate
            3 => 'March', // @translate
            4 => 'April', // @translate
            5 => 'May', // @translate
            6 => 'June', // @translate
            7 => 'July', // @translate
            8 => 'August', // @translate
            9 => 'September', // @translate
            10 => 'October', // @translate
            11 => 'November', // @translate
            12 => 'December', // @translate
        ]);

        $this->dayElement = new Element\Number('day');
        $this->dayElement->setAttributes([
            'class' => 'numeric-datetime-day',
            'step' => 1,
            'min' => 1,
            'max' => 31,
            'placeholder' => 'Day', // @translate
        ]);

        $this->hourElement = new Element\Number('hour');
        $this->hourElement->setAttributes([
            'class' => 'numeric-datetime-hour',
            'step' => 1,
            'min' => 0,
            'max' => 23,
            'placeholder' => 'Hour', // @translate
        ]);

        $this->minuteElement = new Element\Number('minute');
        $this->minuteElement->setAttributes([
            'class' => 'numeric-datetime-minute',
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Minute', // @translate
        ]);

        $this->secondElement = new Element\Number('second');
        $this->secondElement->setAttributes([
            'class' => 'numeric-datetime-second',
            'step' => 1,
            'min' => 0,
            'max' => 59,
            'placeholder' => 'Second', // @translate
        ]);
    }

    public function getValueElement()
    {
        $this->valueElement->setValue($this->getValue());
        return $this->valueElement;
    }

    public function getYearElement()
    {
        return $this->yearElement;
    }

    public function getMonthElement()
    {
        return $this->monthElement;
    }

    public function getDayElement()
    {
        return $this->dayElement;
    }

    public function getHourElement()
    {
        return $this->hourElement;
    }

    public function getMinuteElement()
    {
        return $this->minuteElement;
    }

    public function getSecondElement()
    {
        return $this->secondElement;
    }
}
