<?php if (!defined('APPLICATION')) exit();
// Define the plugin:
$PluginInfo['PurchaseHighlightedDiscussions'] = array(
   'Name' => 'Purchase Highlighted Discussions',
   'Description' => "Allows members to purchase a special Highlight css class for their discussions in the discussion listings.",
   'Version' => '0.2b',
   'RequiredPlugins' => array('MarketPlace' => '0.1.9b'),
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'Author' => 'Paul Thomas',
   'AuthorEmail' => 'dt01pqt_pt@yahoo.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/x00'
);

/*
* # Purchase Anonymous Discussions #
*
* ### About ###
* Allows members to purchase a special Highlight css class for their discussion in the discussions listings.
* 
* ### Sponsor ###
* Special thanks to pxlpshr for making this happen.
*/

class PurchaseHighlightedDiscussions extends Gdn_Plugin {
    
    protected $HasHighlighted = FALSE;
    protected $CanComment = FALSE;
    protected $Highlighted;
    
    public static function PreConditions($UserID,$Product){
        return array('status'=>'pass');
    }
    
    public static function AddHighlightedDiscussions($UserID,$Product,$TransactionID){
        $Quantity=1;
        $VariableMeta=MarketTransaction::GetTransactionMeta($TransactionID);
        $Meta=Gdn_Format::Unserialize($Product->Meta);
        $DefaultQuantity = GetValue('Quantity',$Meta,1);
        $DefaultQuantity = ctype_digit($DefaultQuantity)?$DefaultQuantity:1;
        $Quantity=GetValue('Quantity',$VariableMeta,$DefaultQuantity);
        $HighlightedDiscussions = UserModel::GetMeta($UserID,'HighlightedDiscussions.%','HighlightedDiscussions.');
        UserModel::SetMeta($UserID,array('Quantity'=>GetValue('Quantity',$HighlightedDiscussions,0)+$Quantity),'HighlightedDiscussions.');
        return array('status'=>'success');
        
    }
    
    public static function RemoveHighlightedDiscussions($UserID,$Quantity=1){
        $HighlightedDiscussions = UserModel::GetMeta($UserID,'HighlightedDiscussions.%','HighlightedDiscussions.');
        UserModel::SetMeta($UserID,array('Quantity'=>GetValue('Quantity',$HighlightedDiscussions,0)-$Quantity),'HighlightedDiscussions.');
    }
    
    public function MarketPlace_LoadMarketPlace_Handler($Sender){
        $Options = array(
            'Meta'=>array('Quantity'),
            'RequiredMeta'=>array('Quantity'),
            'ValidateMeta'=>array('Quantity'=>'Integer'),
            'VariableMeta'=>array('Quantity'),
            'ReturnComplete'=>'/profile/highlighteddiscussions'
        );
        $Sender->RegisterProductType('PurchaseHighlightedDiscussions','Allows members to purchase a special Highlight css class for their discussions in the discussion listings',$Options,'PurchaseHighlightedDiscussions::PreConditions','PurchaseHighlightedDiscussions::AddHighlightedDiscussions');
    }
    
    public function ProfileController_HighlightedDiscussions_Create($Sender){
        $HighlightedDiscussions = UserModel::GetMeta(Gdn::Session()->UserID,'HighlightedDiscussions.%','HighlightedDiscussions.');
        $Quantity = GetValue('Quantity',$HighlightedDiscussions,0);
        $Sender->SetData('HighlightedDiscussions',$Quantity);
        $Sender->GetUserInfo(Gdn::Session()->UserID, Gdn::Session()->User->Name);
        $ThemeViewLoc = CombinePaths(array(
            PATH_THEMES, $Sender->Theme,'views', 'purchasehighlighteddiscussions'
        ));
        $View='';
        if(file_exists($ThemeViewLoc.DS.'highlighteddiscussions.php')){
            $View=$ThemeViewLoc.DS.'highlighteddiscussions.php';
        }else{
            $View=dirname(__FILE__).DS.'views'.DS.'highlighteddiscussions.php';
        }
        $Sender->SetTabView('HighlightedDiscussions', $View, 'Profile', 'Dashboard');
        $Sender->Render();
    }
    
    public function ProfileController_AddProfileTabs_Handler($Sender){
        $Sender->AddProfileTab('HighlightedDiscussions','profile/highlighteddiscussions',
                        'HighlightedDiscussions',T('Highlighted Discussions'));
    }
    
    public function Base_BeforeControllerMethod_Handler($Sender,$Args){
        if($Args['Controller']->PageName()!='post')
            return;
        $HighlightedDiscussions = UserModel::GetMeta(Gdn::Session()->UserID,'HighlightedDiscussions.%','HighlightedDiscussions.');
        $this->HasHighlighted = GetValue('Quantity',$HighlightedDiscussions,0);
    }

    public function Base_DiscussionFormOptions_Handler($Sender,$Args) {
        $Discussion = GetValue('Discussion',$Sender)?$Sender->Discussion:(GetValue('Discussion',$Args)?$Args['Discussion']:false);
        $BuyMore = Wrap(T('BuyMoreSpacer',' &nbsp; ').Anchor(T('Buy More'),C('Plugins.MarketPlace.StoreURI','store').'/type/PurchaseHighlightedDiscussions'),'span');
        $BuySome = Wrap(T('BuyMoreSpacer',' &nbsp; ').Anchor(T('Buy Some'),C('Plugins.MarketPlace.StoreURI','store').'/type/PurchaseHighlightedDiscussions'),'span');
        $Message = T('Highlight Post').$BuyMore;
        if(GetValue('DiscussionID',$Discussion))
            $BuySome='';
        if(!$Discussion && $this->HasHighlighted){
            $Args['Options'].='<li>'.$this->ShowOption($Sender->Form,$Message).'</li>';
        }else if($Discussion && $Discussion->Highlighted){
            $Args['Options'].='<li>'.$this->ShowOption($Sender->Form,$Message,array('checked'=>'checked','disabled'=>'disabled'),TRUE).'</li>';
        }else{
            $Message = T('Highlight Post').$BuySome;
            $Args['Options'].='<li>'.$this->ShowOption($Sender->Form,$Message,array('disabled'=>'disabled')).'</li>';
        }
    }
    
    public function PostController_Render_Before($Sender){
        if(strtolower($Sender->RequestMethod)=='question'){
            //$Sender->View=$this->GetView('post.php');
        }
    }

    public function ShowOption($Form,$Message,$Params=array(),$Hidden=FALSE){
        $Options = '';
        $Options .= $Form->CheckBox('Highlighted',$Message,$Params);
        if($Hidden){
            $Form->AddHidden('Highlighted',1);
            $Options .= $Form->Hidden('Highlighted',array('value'=>1));
        }
        return $Options;
    }
    
    public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender,&$Args){
        $Feilds = &$Args['FormPostValues'];
        if(GetValue('DiscussionID',$Feilds)|| !GetValue('Highlighted',$Feilds)){
            if(!$Sender->Discussion->Highlighted){
                $Feilds['HigAnonUserhlighted']=0;
            }
            return;
        }
        if(!$this->HasHighlighted)
            $Feilds['Highlighted']=0;
    }
    
    public function DiscussionModel_AfterSaveDiscussion_Handler($Sender,$Args){
        $Feilds = $Args['FormPostValues'];
        if(!$this->HasHighlighted || !$Feilds['Highlighted'])
            return;
        UserModel::SetMeta(Gdn::Session()->UserID,array('DiscussionID.'.$Feilds['DiscussionID']=>1,'Quantity'=>$this->HasHighlighted-1),'HighlightedDiscussions.');
    }
    
    public function Base_BeforeDiscussionName_Handler($Sender,&$Args){
        $CssClass=&$Args['CssClass'];
        $Discussion=$Args['Discussion'];
        if($Discussion->Highlighted){
            $CssClass .= ' Highlight';
        }
    }
    
    public function Base_BeforeDispatch_Handler($Sender){
        
        if(C('Plugins.PurchaseHighlightedDiscussions.Version')!=$this->PluginInfo['Version'])
            $this->Structure();
    }
    
    public function Setup() {

        $this->Structure();
    }
    
    public function Structure(){
        Gdn::Structure()
            ->Table('Discussion')
            ->Column('Highlighted','int(4)',0)
            ->Column('HighlightedHash','char(32)',null)
            ->Set();
            
        SaveToConfig('Plugins.PurchaseHighlightedDiscussions.Version', $this->PluginInfo['Version']);
        
    }
    
    
    

}
