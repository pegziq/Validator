<?php

/*
 * This file is part of Alt Three Validator.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AltThree\Validator;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * This is the model validating observer class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class ValidatingObserver
{
    /**
     * The validation factory instance.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $factory;

    /**
     * Create a new validating observer instance.
     *
     * @param \Illuminate\Contracts\Validation\Factory $factory
     *
     * @return void
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Validate the model on saving.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \AltThree\Validator\ValidationException
     *
     * @return void
     */
    public function saving(Model $model)
    {
        $this->validate($model);
    }

    /**
     * Validate the model on restoring.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \AltThree\Validator\ValidationException
     *
     * @return void
     */
    public function restoring(Model $model)
    {
        $this->validate($model);
    }

    /**
     * Validate the given model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \AltThree\Validator\ValidationException
     *
     * @return void
     */
    protected function validate(Model $model)
    {
        $attributes = $model->getAttributes();

        $messages = isset($model->validationMessages) ? $model->validationMessages : [];

        if (method_exists($model, 'configure')) {
            $arr = (array)$model::configure();
            if (!empty($arr)) {
                $intersect = array_intersect_key($model->rules, $arr);
                foreach ($intersect as $k => $v) {
                    if (strpos($v, 'in_config') === -1) continue;
                    $func = explode('|', $v);
                    foreach ($func as $m => $n) {
                        if (strpos($n, 'in_config') === 0) {
                            $func[$m] = 'in:' . implode(array_keys($arr[$k]), ',');
                        }
                    }
                    $model->rules[$k] = implode('|', $func);
                }
            }
        }

        $validator = $this->factory->make($attributes, $model->rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator->getMessageBag());
        }

        if (method_exists($model, 'validate')) {
            $model->validate();
        }
    }
}
