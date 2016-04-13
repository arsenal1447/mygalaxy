<?php

namespace star\payment\models;

use star\order\models\Order;
use star\payment\models\paypal\PayPal;
use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Url;

/**
 * This is the model class for table "payment".
 *
 * @property integer $payment_id
 * @property integer $order_id
 * @property integer $payment_method
 * @property string $payment_fee
 * @property string $transcation_no
 * @property integer $create_at
 * @property integer $update_at
 * @property integer $status
 */
class Payment extends \yii\db\ActiveRecord
{
    const ALIPAY = 1;
    const PAYPAL = 2;

    const STATUS_WAIT_BUYER_PAY = 0;
    const STATUS_BUYER_PAY = 1;

    public function getPaymentMethod(){
        return [
            self::ALIPAY => Yii::t('payment','AliPay'),
            self::PAYPAL => Yii::t('payment','PayPal'),
        ];
    }

    public function getStatus(){
        return [
            self::STATUS_WAIT_BUYER_PAY => Yii::t('payment','Waited to be Paid'),
            self::STATUS_BUYER_PAY => Yii::t('payment','Paid'),
        ];
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'payment_method', 'payment_fee', 'transcation_no',  'status'], 'required'],
            [['order_id', 'payment_method', 'create_at', 'status','update_at'], 'integer'],
            [['payment_fee'], 'number'],
            [['transcation_no'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'payment_id' => Yii::t('payment', 'Payment ID'),
            'order_id' => Yii::t('payment', 'Order ID'),
            'payment_method' => Yii::t('payment', 'Payment Method'),
            'payment_fee' => Yii::t('payment', 'Payment Fee'),
            'transcation_no' => Yii::t('payment', 'Transcation No'),
            'create_at' => Yii::t('payment', 'Create At'),
            'status' => Yii::t('payment', 'Status'),
        ];
    }

    public function behaviors()
    {
        return [
            'time' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_at',
                'updatedAtAttribute' => 'update_at',
            ]
        ];
    }

    public function getPayList(){
        return [
            self::ALIPAY=>Yii::t('payment','AliPay'),
            self::PAYPAL=>Yii::t('payment','PayPal'),
        ];
    }

    public function getRedirectUrl($payMethod,$orderId){
        switch($payMethod){
            case  self::ALIPAY:
                return Url::to(['/payment/home/alipay/index', 'id' => $orderId]);
            case  self::PAYPAL:
                return Yii::createObject(PayPal::className())->pay($orderId);
        }
    }

    public function afterSave($insert, $changedAttributes){
        if(!$insert&& $this->status){
            $order =$this->order;
            $order->status = $order::STATUS_WAIT_SHIPMENT;
            if(!$order->save()){
                throw new Exception('change order status fail');
            }
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function getOrder(){
        return $this->hasOne(Order::className(),['order_id'=>'order_id']);
    }
}
