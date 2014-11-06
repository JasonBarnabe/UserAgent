<?php if (!defined('APPLICATION')) exit();

$PluginInfo['UserAgent'] = array(
  'Name' => 'User Agent',
  'Description' => "Record user agent and display it and browser icon above posts.",
  'Version' => '2.1',
  'MobileFriendly' => TRUE,
  'Author' => "Jason Barnabe",
  'AuthorEmail' => 'jason.barnabe@gmail.com',
  'AuthorUrl' => 'https://github.com/JasonBarnabe/UserAgent'
);

class UserAgentPlugin extends Gdn_Plugin {

  public $Logos = array(
    'Chrome' => 'chrome.png',
    'Chromium' => 'chromium.png',
    'Firefox' => 'firefox.png',
    'IE' => 'ie.png',
    'Maxthon' => 'maxthon.png',
    'Mercury' => 'mercury.png',
    'Opera' => 'opera.png',
    'PaleMoon' => 'palemoon.png',
    'Safari' => 'safari.png'
  );

  public function Base_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('useragent.css', FALSE, FALSE));
  }

  // Comments display
  public function DiscussionController_CommentInfo_Handler($Sender, $Args) {
    $Attributes = GetValue('Attributes', GetValue('Comment', $Args));
    $this->AttachInfo($Sender, $Attributes);
  }

  // Comments, after saving an edit
  public function PostController_CommentInfo_Handler($Sender, $Args) {
    $this->DiscussionController_CommentInfo_Handler($Sender, $Args);
  }

  // Discussions display
  public function DiscussionController_DiscussionInfo_Handler($Sender, $Args) {
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
    $UserAgent = GetValue('HTTP_USER_AGENT', $_SERVER);
    $Args['FormPostValues']['Attributes']['UserAgent'] = GetValue('HTTP_USER_AGENT', $_SERVER);
    $BrowserData = @get_browser($UserAgent); // requires browsecap.ini
    if ($BrowserData) {
      $Args['FormPostValues']['Attributes']['Browser'] = $BrowserData->browser;
    }

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
    $Browser = GetValue('Browser', $Attributes);
    if ($UserAgent) {
      if ($Browser) {
        $Logo = $this->Logos[$Browser];
        if ($Logo) {
          $Info = Img($this->GetResource('logos/'.$Logo, FALSE, FALSE), array('alt' => htmlspecialchars($Browser)));
        } else {
          $Info = htmlspecialchars($Browser);
        }
      }
      If (!$Info) {
        $Info = '[?]';
      }

      echo Wrap($Info, 'span', array('class' => 'MItem UserAgent', 'title' => htmlspecialchars($UserAgent)));
    }
  }
}
