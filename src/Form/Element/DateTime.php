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

        $this->valueElement = (new Element\Hidden($name))
            ->setAttribute('class', 'numeric-datetime-value');
        $this->yearElement = (new Element\Number('year'))
            ->setAttributes([
                'class' => 'numeric-datetime-year',
                'step' => 1,
                'min' => self::YEAR_MIN,
                'max' => self::YEAR_MAX,
                'placeholder' => 'Year', // @translate
            ]);
        $this->monthElement = (new Element\Select('month'))
            ->setAttribute('class', 'numeric-datetime-month')
            ->setEmptyOption('Month') // @translate
            ->setValueOptions($this->getMonthValueOptions());
        $this->dayElement = (new Element\Select('day'))
            ->setAttribute('class', 'numeric-datetime-day')
            ->setEmptyOption('Day') // @translate
            ->setValueOptions($this->getDayValueOptions());
        $this->hourElement = (new Element\Select('hour'))
            ->setAttribute('class', 'numeric-datetime-hour')
            ->setEmptyOption('Hour') // @translate
            ->setValueOptions($this->getHourValueOptions());
        $this->minuteElement = (new Element\Select('minute'))
            ->setAttribute('class', 'numeric-datetime-minute')
            ->setEmptyOption('Minute') // @translate
            ->setValueOptions($this->getMinuteSecondValueOptions());
        $this->secondElement = (new Element\Select('second'))
            ->setAttribute('class', 'numeric-datetime-second')
            ->setEmptyOption('Second') // @translate
            ->setValueOptions($this->getMinuteSecondValueOptions());
    }

    public function getMonthValueOptions()
    {
        return [
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
        ];
    }

    public function getDayValueOptions()
    {
        return array_combine(range(1, 31), range(1, 31));
    }

    public function getHourValueOptions()
    {
        return [
            0 => '00 (12 am)', // @translate
            1 => '01 (1 am)', // @translate
            2 => '02 (2 am)', // @translate
            3 => '03 (3 am)', // @translate
            4 => '04 (4 am)', // @translate
            5 => '05 (5 am)', // @translate
            6 => '06 (6 am)', // @translate
            7 => '07 (7 am)', // @translate
            8 => '08 (8 am)', // @translate
            9 => '09 (9 am)', // @translate
            10 => '10 (10 am)', // @translate
            11 => '11 (11 am)', // @translate
            12 => '12 (12 pm)', // @translate
            13 => '13 (1 pm)', // @translate
            14 => '14 (2 pm)', // @translate
            15 => '15 (3 pm)', // @translate
            16 => '16 (4 pm)', // @translate
            17 => '17 (5 pm)', // @translate
            18 => '18 (6 pm)', // @translate
            19 => '19 (7 pm)', // @translate
            20 => '20 (8 pm)', // @translate
            21 => '21 (9 pm)', // @translate
            22 => '22 (10 pm)', // @translate
            23 => '23 (11 pm)', // @translate
        ];
    }

    public function getMinuteSecondValueOptions()
    {
        return array_map(function($n) {return sprintf('%02d', $n);}, range(0, 59));
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
