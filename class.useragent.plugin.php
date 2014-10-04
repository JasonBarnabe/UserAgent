<?php if (!defined('APPLICATION')) exit();

$PluginInfo['UserAgent'] = array(
  'Name' => 'User Agent',
  'Description' => "Record user agent and display it and browser icon above posts.",
  'Version' => '2.0',
  'MobileFriendly' => TRUE,
  'Author' => "Jason Barnabe",
  'AuthorEmail' => 'jason.barnabe@gmail.com',
  'AuthorUrl' => 'https://github.com/JasonBarnabe'
);

class UserAgentPlugin extends Gdn_Plugin {

  public $Logos = array(
    'Firefox' => 'firefox.png',
    'Chrome' => 'chrome.png',
    'Internet Explorer' => 'ie.png',
    'Opera' => 'opera.png',
    'Safari' => 'safari.png'
  );

  public function Base_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('useragent.css', FALSE, FALSE));
  }

  public function DiscussionController_InsideCommentMeta_Handler($Sender, $Args) {
    $Attributes = GetValue('Attributes', GetValue('Comment', $Args));
    $this->AttachInfo($Sender, $Attributes);
  }

  public function DiscussionController_AfterDiscussionMeta_Handler($Sender, $Args) {
    $Attributes = GetValue('Attributes', GetValue('Discussion', $Args));
    $this->AttachInfo($Sender, $Attributes);
  }
   
  public function CommentModel_BeforeSaveComment_Handler($Sender, &$Args) {
    if ($Args['FormPostValues']['InsertUserID'] != Gdn::Session()->UserID)
      return;
    $this->SetAttributes($Sender, $Args);
  }

  public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, &$Args) {
    if ($Args['FormPostValues']['InsertUserID'] != Gdn::Session()->UserID)
      return;
    $this->SetAttributes($Sender, $Args);
  }

  /**
   * Collect user agent data and save in Attributes array.
   */
  protected function SetAttributes($Sender, &$Args) {
    if (!isset($Args['FormPostValues']['Attributes'])) {
      $Args['FormPostValues']['Attributes'] = array();
    } else {
      $Args['FormPostValues']['Attributes'] = unserialize($Args['FormPostValues']['Attributes']);
    }

    // Add user agent data to Attributes
    $Args['FormPostValues']['Attributes']['UserAgent'] = GetValue('HTTP_USER_AGENT', $_SERVER);

    $Args['FormPostValues']['Attributes'] = serialize($Args['FormPostValues']['Attributes']);
  }

  /**
   * Output user agent information.
   */
  protected function AttachInfo($Sender, $Attributes) {
    $Info = null;
    # Vanilla 2.1 - comment model passes in serialized string - fixed in https://github.com/vanilla/vanilla/commit/7e86195f0c5ddaf42a5a281341176e5ab2de9de0
    if (is_string($Attributes)) {
      $Attributes = unserialize($Attributes);
    }
    $UserAgent = GetValue('UserAgent', $Attributes);
    if ($UserAgent) {
      $Data = @get_browser($UserAgent); // requires browsecap.ini
      if ($Data) {
        $Logo = $this->Logos[$Data->browser];
        if ($Logo) {
          $Info = Img($this->GetResource('logos/'.$Logo, FALSE, FALSE), array('alt' => htmlspecialchars($Data->browser)));
        } else {
          $Info = htmlspecialchars($Data->browser);
        }
      }
      If (!$Info) {
        $Info = '[?]';
      }

      echo Wrap($Info, 'span', array('class' => 'MItem UserAgent', 'title' => htmlspecialchars($UserAgent)));
    }
  }
}
