  <p><b><u>User Management</u></b></p>
  <table id="userlist" class="table">
  <thead>
    <tr>
      <th>Actions</th>
      <th>Name</th>
      <?if($platform != "mobile") {?>
      <th>Email</th>
      <?}?>
    </tr>
  </thead>
  <tbody>
  {{each(u, user) users}}
    <tr id="tr_user_${user.id}" data-id="${user.id}" data-name="${user.name}">
      <td>
        <a id="edit_${user.id}" class="aEditUser" title="Edit User" href="#"><img id="imgEditUser" class="icon-edit" src="/images/blank.gif" /></a>
        <a id="delete_${user.id}" class="aDeleteUser" title="Delete User" href="#"><img id="imgDeleteUser" class="icon-delete" src="/images/blank.gif" /></a>
        <a id="reset_${user.id}" class="aResetUser" title="Reset Password" href="#"><img id="imgResetUser" class="icon-reset" src="/images/blank.gif" /></a>
      </td>
      <td>${user.name}</td>
      <?if($platform != "mobile") {?>
      <td>${user.email}</td>
      <?}?>
    </tr>
    <tr class="trEditUser" id="tr_edit_${user.id}">
      <td colspan="3">
        <form id="frmEditUser_${user.id}" name="frmEditUser" class="frmEditUser" action="/cgi/fc/edit.user" method="post">
          <input name="hidEditUserId" type="hidden" value="edit_${user.id}" />
          Name: <input id="txtEditUserName_${user.id}" class="txtEditUser" name="txtEditUserName" type="text" value="${user.name}" /><br>
          Email: <input id="txtEditUserEmail_${user.id}" class="txtEditUser" name="txtEditUserEmail" type="text" value="${user.email}" /><br/>
          Stage: <input id="txtEditUserStage_${user.id}" class="txtEditUser" name="txtEditUserStage" type="text" value="${user.stage}" /><br/>
          Bad Logins: <input id="txtEditUserBadLogins_${user.id}" class="txtEditUser" name="txtEditUserBadLogins" type="text" value="${user.badlogins}" /><br/>
          Activated: <input id="txtEditUserActivated_${user.id}" class="chkEditUser" name="txtEditUserActivated" type="checkbox" {{if user.active == 1}}checked{{/if}} /><br/>
          Admin: <input id="chkEditUserAdmin_${user.id}" class="chkEditUser" name="chkEditUserAdmin" type="checkbox" {{if user.admin == 1}}checked{{/if}} /><br/>
          <img class="spinner" src="/images/spinner.gif" alt="" /><input id="btnEditUserSubmit_${user.id}" class="btn-primary" name="btnEditUserSubmit" type="submit" value="Apply Changes" />
        </form>
      </td>
    </tr>
   {{/each}}
  </tbody>
  </table>
