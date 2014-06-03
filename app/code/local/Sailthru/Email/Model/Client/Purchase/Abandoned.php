<?php
/**
 * Client Purchase Abandoned Model
 *
 * @category  Sailthru
 * @package   Sailthru_Email
 *
 */
class Sailthru_Email_Model_Client_Purchase_Abandoned extends Sailthru_Email_Model_Client_Purchase
{

    protected $_cart;

    protected $_template;

    protected $_subject;

    protected $_senderEmail;

    protected $_senderName;

    protected $_messageId;

    protected $_reminderTime;

    public function __construct()
    {
        $this->_cart = Mage::getStoreConfig('sailthru/email/abandoned_cart', $this->_storeId);
        $this->_template = Mage::getStoreConfig('sailthru/email/abandoned_cart_template', $this->_storeId);
        $this->_subject = Mage::getStoreConfig('sailthru/email/abandoned_cart_template', $this->_storeId);
        $this->_senderEmail = Mage::getStoreConfig('sailthru/email/abandoned_cart_sender_email', $this->_storeId);
        $this->_senderName = Mage::getStoreConfig('sailthru/email/abandoned_cart_sender_name', $this->_storeId);
        $this->_messageId = isset($_COOKIE['sailthru_bid']) ? $_COOKIE['sailthru_bid'] : null;
        $this->_reminderTime = '+' . Mage::helper('sailthruemail')->getReminderTime() . ' min';
    }

    public function sendCart($cart,$email)
    {
        try{
            $data = array(
                'email' => $email,
                'incomplete' => 1,
                'items' => $this->_getItems($cart),
                'reminder_time' => $this->_reminderTime,
                'reminder_template' => $this->_template,
                'message_id' =>$this->_messageId
            );

            $response = $this->apiPost('purchase', $data);

            //For future iterations, use switch statement to handle multiple error messages.
            if($response['error'] == 14) {
                /**
                 * Response Error 14 means that an unknown template was passed in the API call.
                 * This normally happens for first time API calls or when the name of the template has
                 * been changed, http://getstarted.sailthru.com/api/api-response-errors.  We'll
                 * therefore need to create a template to pass in the call.  One condition for the
                 * template to be created is that the sender email must be verified so please check
                 * https://my.sailthru.com/verify to make sure that the send email is listed there.
                 *
                 *Create Abandoned Cart Email
                 */
                $newTemplate = array("template" => $this->_cartTemplate,
                        'content_html' => $this->_getContent(),
                        'subject' => $this->_subject,
                        'from_name' => $this->_senderName,
                        'from_email' => $this->_senderEmail,
                        'is_link_tracking' => 1,
                        'is_google_analytics' => 1
                );
                $create_template = $this->apiPost('template', $newTemplate);

                //Send Purchase Data
                $response = $this->apiPost('purchase', $data);
                $data = array('email' => $email, '=incomplete' => 1, 'items' => $this->shoppingCart());
                $response = $this->apiPost("purchase", $data);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    private function _createContent()
    {
        //It's important to note that the code below only works if routed through Sailthru.
        $content_html = '{*Sailthru zephyr code is used for full functionality*}
                                    <div id="main">
                                        <table width="700">
                                            <tr>
                                                <td>
                                                    <h2><p>Hello {profile.vars.name}</p></h2>
                                                    <p>Did you forget the following items in your cart?</p>

                                                    <table>
                                                        <thead>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <div><span style="display:block;text-align:center;color:white;font-size:13px;font-weight:bold;padding:15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{profile.purchase_incomplete.items[0].vars.checkout_url}">Re-Order Now!</a></span></div>
                                                                </td>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                        {sum = 0}
                                                        {foreach profile.purchase_incomplete.items as i}
                                                            <table width="650" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 20px 0;background:#fff;border:1px solid #e5e5e5">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="padding:20px"><a href="{i.url}"><img width="180" height="135" border="0" alt="{i.title}" src="{i.vars.image_url}"></a></td>
                                                                        <td width="420" valign="top" style="padding:20px 10px 20px 0">
                                                                            <div style="padding:5px 0;color:#333;font-size:18px;font-weight:bold;line-height:21px">{i.title}</div>
                                                                            <div style="padding:0 0 5px 0;color:#999;line-height:21px;margin:0px">{i.vars.currency}{i.price/100}</div>
                                                                            <div style="color:#999;font-weight:bold;line-height:21px;margin:0px">{i.description}</div>
                                                                            <div><span style="display:block;text-align:center;width:120px;border-left:1px solid #b43e2e;border-right:1px solid #b43e2e;color:white;font-size:13px;font-weight:bold;padding:0 15px;text-shadow:0 -1px 1px #af301f;white-space:nowrap;text-transform:uppercase;letter-spacing:1;background-color:#d14836;min-height:29px;line-height:29px;margin:0 0 0 0;border:1px solid #af301f;margin-top:5px"><a href="{i.url}">Buy Now</a></span></div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        {/foreach}
                                                        <tr>
                                                            <td align="left" valign="top" style="padding:3px 9px" colspan="2"></td>
                                                            <td align="right" valign="top" style="padding:3px 9px"></td>
                                                        </tr>

                                                        </tbody>

                                                        <tfoot>

                                                        </tfoot>
                                                    </table>

                                                    <p><small>If you believe this has been sent to you in error, please safely <a href="{optout_confirm_url}">unsubscribe</a>.</small></p>
                                                    {beacon}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>'; //include css or tables here to style e-mail.
        //It's important that the "from_email" is verified, otherwise the code below will not work.
        return $content_html;
