//var target_attr = "required";
function applyAttribute(target_attr){
    var affected_fields = document.getElementsByClassName('field-' + target_attr);
    for(var i = 0;i < affected_fields.length;i++){
        affected_fields[i].setAttribute(target_attr,target_attr);
    }
}
applyAttribute("required");
applyAttribute("readonly");
