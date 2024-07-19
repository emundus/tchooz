import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    users: {},
    currentUser: 0,
    displayedUser: 0,
    rights: {},
  }),
  getters: {
    getUsers: state => state.users,
    getUserById: (state) => {
      return (userId) => state.users[userId];
    }
  },
  actions: {
    setUsers(users) {
      if (users && users.length > 0) {
        users.forEach(user => {
          this.users[user.user_id] = user;
        });
      }
    },
    setCurrentUser(user) {
      this.currentUser = user;
    },
    setDisplayedUser(user) {
      this.displayedUser = user;
    },
    setAccessRights(data) {
      this.rights[data.fnum] = data.rights;
    },
  }
});