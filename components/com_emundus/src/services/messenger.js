import {FetchClient} from './fetchClient.js';

const client = new FetchClient('messenger');

export default {
  async getFilesByUser() {
    try {
      return await client.get('getfilesbyuser');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async getChatroomsByUser() {
    try {
      return await client.get('getchatroomsbyuser');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async getChatroomsByFnum(fnum) {
    try {
      return await client.get('getchatroomsbyfnum', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async getMessagesByFnum(fnum) {
    try {
      return await client.get('getmessagesbyfnum', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async createChatroom(fnum) {
    try {
      return await client.post('createchatroom', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async closeChatroom(fnum) {
    try {
      return await client.post('closechatroom', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async openChatroom(fnum){
    try {
      return await client.post('openchatroom', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async sendMessage(message, fnum) {
    try {
      return await client.post('sendmessage', {message, fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async markAsRead(chatroom_id) {
    try {
      return await client.post('markasread', {chatroom_id});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async goToFile(fnum) {
    try {
      return await client.post('gotofile', {fnum});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
}