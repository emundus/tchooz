export class FetchClient {
  constructor(controller) {
    this.baseUrl = '/index.php?option=com_emundus&controller=' + controller;
  }

  async get(task, params, signal = null) {
    let url = this.baseUrl + '&task=' + task;

    if (params) {
      for (let key in params) {
        url += '&' + key + '=' + params[key];
      }
    }

    return fetch(url, signal ? { signal: signal } : undefined)
        .then(response => {
          if (response.ok) {
            return response.json();
          } else {
            throw new Error('An error occurred while fetching the data. ' + response.status + ' ' + response.statusText + '.');
          }
        }).then(data => {
          return data;
        }).catch(error => {
          throw new Error('An error occurred while fetching the data. ' + error.message + '.');
        });



  }

  async post(task, data, headers = null, timeout = 10000) {
    let url = this.baseUrl + '&task=' + task;

    let formData = new FormData();
    for (let key in data) {
      formData.append(key, data[key]);
    }

    let parameters = {
      method: 'POST',
      body: formData
    };

    if (headers) {
      parameters.headers = headers;
    }

    if(timeout) {
      parameters.signal = AbortSignal.timeout(timeout);
    }

    return fetch(url, parameters).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error('An error occurred while fetching the data. ' + response.status + ' ' + response.statusText + '.');
      }
    }).then(data => {
      return data;
    }).catch(error => {
      if (err.name === "TimeoutError") {
        throw new Error('The request timed out. ' + error.message + '.');
      } else {
        throw new Error('An error occurred while fetching the data. ' + error.message + '.');
      }
    });
  }

  async delete(task, params) {
    let url = this.baseUrl + '&task=' + task;

    if (params) {
      for (let key in params) {
        url += '&' + key + '=' + params[key];
      }
    }

    return fetch(url, {
      method: 'DELETE'
    }).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error('An error occurred while fetching the data. ' + response.status + ' ' + response.statusText + '.');
      }
    }).then(data => {
      return data;
    }).catch(error => {
      throw new Error('An error occurred while fetching the data. ' + error.message + '.');
    });
  }
}