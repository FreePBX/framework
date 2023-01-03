<?php
    if ($activetab == "licenses") {
        $c = 'class="tab-pane active"';
    } else {
        $c = 'class="tab-pane"';
    }
    ?>
    <div role="tabpanel" <?php echo $c; ?> id="licensestab">
        <div class="container-fluid">
            <?php
                echo "<h3>"._("Commercial Licenses details.")."</h3>";
            ?>
            <div class='container-fluid'>
                <?php
                    echo show_help( _("The table below shows you the list of all trade modules and their status. You will know when the license will expire and when its support and/or update will also expire."), _("Commercial Licenses"), false, false);
                ?>
            </div>
            <div class="row">
                <div class="col-md-2">
                </div>
                <div class="col-md-8">
                    <div class='container-fluid'>            
                        <table
                        id="table"
                        data-toggle="table"
                        data-flat="true"
                        data-search="true"
                        >
                            <thead>
                                <tr>
                                <th data-halign="center" data-width="100%" data-field="module" data-sortable="true"><?php echo _("Modules") ?></th>
                                <th data-halign="center" data-width="30" data-field="status" data-sortable="true"><?php echo _("Status") ?></th>
                                <th data-halign="center" data-width="150" data-field="module_expiry" data-sortable="true"><?php echo _("Module expiry") ?></th>
                                <?php 
                                if(!empty($licenses["updates"])){
                                ?>
                                <th data-halign="center" data-width="150" data-field="support_expiry" data-sortable="true"><?php echo _("Support / Update expiry") ?></th>
                                <?php 
                                }
                                ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    if(!empty($licenses["updates"])){
                                        $update = json_decode(base64_decode($licenses["updates"]), true);
                                    }
                                    $now = strtotime("now");
                                    foreach($licenses as $key => $content){
                                        $upd    = empty($update[$key]) ? _("None") : date("Y-m-d",$update[$key]);
                                        $expiry = empty($content) ? _("None") : $content;
                                        if($expiry != _("None") && !preg_match('/\d{4}-\d{2}-\d{2}/', $expiry)){
                                            continue;
                                        };
                                        $status = "<i class='fa fa-check'></i>";

                                        /**
                                         * Payload
                                         * Support -30    : 0001 : 1
                                         * Support expiry : 0010 : 2
                                         * Modules -30    : 0100 : 4
                                         * Module expiry  : 1000 : 8 
                                         * 
                                         */

                                        $payload = 0;
                                        if((strtotime($upd)-(30*86400))-$now <= 0 && $upd != _("None")){
                                            $payload += 1;
                                        }
                                        if(strtotime($upd)-$now <= 0 && $upd != _("None")){
                                            $payload += 2;
                                        }
                                        if((strtotime($content)-(30*86400))-$now <= 0){   
                                            $payload += 4;
                                        }                                
                                        if(strtotime($content)-$now <= 0){
                                            $payload += 8;
                                        }

                                        if(!empty($mod_lic[$key]["name"]) && $mod_lic[$key]["name"] != "SmartOffice Bundles" && $mod_lic[$key]["name"] != "Zulu Users" && $mod_lic[$key]["name"] != "Global License Expiration"){
                                            $infoM = $infoS = $text_ex = "";
                                            $classM = $classS = "class='licColStd'";
                                            $msgS30    = _("The Support will expire in less than 30 days.");
                                            $msgS      = _("The Support has expired.");
                                            $msgM30    = _("The Module will expire in less than 30 days.");
                                            $msgM      = _("The Module has expired.");

                                            /**
                                             * Leave this debug line here please.
                                             * dbug($mod_lic[$key]["name"]."paiload = $payload");
                                             */

                                            switch($payload){
                                                case 0:
                                                    $status = "<i class='fa fa-check'></i>";
                                                    break;
                                                case 1:                                            
                                                    $classS = "class='maAlert30days'";
                                                    $infoS  = "title='".$msgS30."'";
                                                    $status = "<i class='fa fa-clock-o fa-lg'></i>";
                                                    break;
                                                case 2:
                                                case 3:
                                                    $classS = "class='maAlertExpiry'";
                                                    $infoS  = "title='".$msgS."'";
                                                    $status = "<i class='fa fa-check'></i>";
                                                    break;                                            
                                                case 4:
                                                    $classM = "class='maAlert30days'";
                                                    $infoM  = "title='".$msgM30."'";
                                                    $status = "<i class='fa fa-clock-o fa-lg'></i>";
                                                    break;
                                                case 5:
                                                    $classM = "class='maAlert30days'";
                                                    $classS = "class='maAlert30days'";
                                                    $infoS  = "title='".$msgS30."'";
                                                    $infoM  = "title='".$msgM30."'";
                                                    $status = "<i class='fa fa-clock-o fa-lg'></i>";
                                                    break;
                                                case 6:
                                                case 7:
                                                    $classM = "class='maAlert30days'";
                                                    $classS = "class='maAlertExpiry'";
                                                    $infoS  = "title='".$msgS."'";
                                                    $infoM  = "title='".$msgM30."'";
                                                    $status = "<i class='fa fa-clock-o fa-lg'></i>";
                                                    break;
                                                case 10:
                                                    $classM = "class='maAlert30days'";
                                                    $info   = "title='".$msg."'";
                                                    $status = "<i class='fa fa-clock-o fa-lg'></i>";
                                                    break;
                                                case 8:
                                                case 9:
                                                case 11:
                                                case 12:
                                                case 13:
                                                case 14:
                                                case 15:
                                                    $classS  = "class='maAlertExpiry'";
                                                    $classM  = "class='maAlertExpiry'";
                                                    $infoS   = "title='".$msgS."'";
                                                    $infoM   = "title='".$msgM."'";
                                                    $status  = "<i class='fa fa-times'></i>";
                                                    $text_ex = "text-expired";
                                                    
                                                    break;
                                            }
                                            echo "<tr>";
                                            echo "\t<td class='licCol'><i class='fa fa-tasks'></i> <strong class='$text_ex'>".$mod_lic[$key]["name"]."</strong></td>";
                                            echo "\t<td class='text-center licCol'>$status</td>";
                                            echo "\t<td $classM><span $infoM>$expiry</span></td>";
                                            if(!empty($licenses["updates"])){
                                                echo "\t<td $classS><span $infoS>$upd</span></td>";
                                            }
                                            echo "</tr>";
                                        }
                                    }
                                ?>
                            </tbody>
                        </table>
                        <p>
                            <br>
                        </p>
                    </div>
                </div>
                <div class="col-md-2">
                </div>
            </div>
        </div>
    </div>
