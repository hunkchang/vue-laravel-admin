<?php
/**
 * Created by PhpStorm.
 * User: 32823
 * Date: 2017/3/15
 * Time: 15:33
 */
namespace Models\Common;

class ValidatorFrom
{
    protected $rules;


    public function toJs($rules)
    {

        $jsRules     = [];
        $relusResult = [];
        foreach ($rules as $key => $rule) {

            $ruleArrsTemp = explode('|', $rule);
            $ruleArrs     = [];
            foreach ($ruleArrsTemp as $ruleVal) {

                if (empty($ruleVal)) continue;

                $ruleValArr               = explode(':', $ruleVal);
                $ruleArrs[$ruleValArr[0]] = isset($ruleValArr[1]) ? $ruleValArr[1] : '';

                $relusResult[$key][$ruleValArr[0]] = isset($ruleValArr[1]) ? $ruleValArr[1] : '';
            }

            $min                      = null;
            $max                      = null;
            $requireType = 'string';
            foreach ($ruleArrs as $rulekey => $ruleValue) {
                switch ($rulekey) {
                    case  'required': //必填项
                        $jsRules[$key]['validators']['notEmpty'] = json_decode("{}");//'message' => trans('admin::' . CONTROLLER . '.table.' . $key) . '不能为空'
                        break;
                    case 'integer':
                        $requireType = 'integer';
                        $jsRules[$key]['validators']['integer'] = json_decode("{}");//'message' => trans('admin::' . CONTROLLER . '.table.' . $key) . '不能为空'
                        break;
                    case 'min':
                    case 'max':
                        if ($requireType =='string'){
                            $jsRules[$key]['validators']['stringLength'][$rulekey] = $ruleValue;
                        }elseif ($requireType == 'integer'){
                            $jsRules[$key]['validators']['between'][$rulekey] = $ruleValue;
                        }
                        break;
                    case 'email':
                        $jsRules[$key]['validators']['emailAddress'] = json_decode("{}");//'message' => trans('admin::' . CONTROLLER . '.table.' . $key) . '不正确'
                        break;
                    case 'regexp':
                        $jsRules[$key]['validators']['regexp'] = ['regexp' => '/^[a-zA-Z0-9]+$/']; // 'message' => '名称由字母或数字组成'
                        break;
//                    case 'unique':
//                        $jsRules[$key]['validators']['regexp'] = ['regexp' => '/^[a-zA-Z0-9_\.]+$/', 'message' => '名称由数字字母下划线和.组成'];
//                        break;
                }
            }

            if ($key == 'password_confirmation')
            {
                $jsRules[$key]['validators']['identical']['field'] = 'password';
            }

        }

        if (empty($jsRules)) {
            $jsRules = [];
        }
        return ['jsArr' => json_encode($jsRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'phpArr' => $relusResult];
    }
}